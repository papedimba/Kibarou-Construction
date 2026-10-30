<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Backup = one zip: database.sql (pure PHP dump, one statement per line), manifest.json, and the uploaded files
 * (public/uploads, storage/private). Works on shared hosting: no mysqldump, no shell.
 */
final class Backup
{
    private const FILE_DIRS = ['public/uploads', 'storage/private'];
    private const ROWS_PER_INSERT = 100;

    private string $root;

    public function __construct(private Database $db, string $root = '')
    {
        $this->root = $root ?: dirname(__DIR__, 2);
    }

    public function dir(): string
    {
        $d = $this->root . '/storage/backups';
        is_dir($d) || mkdir($d, 0750, true);
        return $d;
    }

    /** @return string absolute path of the new archive */
    public function create(): string
    {
        $path = $this->dir() . '/kibarou-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';
        $sql = tempnam(sys_get_temp_dir(), 'kibsql');
        $this->dump($sql);
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
            throw new \RuntimeException('Impossible de créer l\'archive de sauvegarde.');
        }
        $zip->addFile($sql, 'database.sql');
        $zip->addFromString('manifest.json', json_encode([
            'app' => 'kibarou-cms', 'created_at' => date('c'), 'format' => 1,
            'migrations' => array_column($this->db->all('SELECT name FROM migrations ORDER BY name'), 'name'),
        ], JSON_UNESCAPED_SLASHES));
        foreach (self::FILE_DIRS as $rel) {
            $base = "$this->root/$rel";
            if (!is_dir($base)) {
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)) as $f) {
                if ($f->isFile() && !$f->isLink() && $f->getFilename() !== '.htaccess' && $f->getFilename() !== '.gitkeep') {
                    $zip->addFile($f->getPathname(), 'files/' . $rel . '/' . substr($f->getPathname(), strlen($base) + 1));
                }
            }
        }
        $zip->close();
        unlink($sql);
        chmod($path, 0640);
        return $path;
    }

    /** Keeps the N newest archives and deletes the others. @return int number deleted */
    public function prune(int $keep): int
    {
        $all = glob($this->dir() . '/kibarou-*.zip') ?: [];
        rsort($all); // names start with a sortable timestamp
        $old = array_slice($all, max(1, $keep));
        array_map('unlink', $old);
        return count($old);
    }

    /** @return list<array{name:string,size:int,date:string}> newest first */
    public function list(): array
    {
        $all = glob($this->dir() . '/kibarou-*.zip') ?: [];
        rsort($all);
        return array_map(fn($f) => ['name' => basename($f), 'size' => filesize($f), 'date' => date('Y-m-d H:i', filemtime($f))], $all);
    }

    /** Resolves a user-supplied backup name to a path inside storage/backups, or null. */
    public function path(string $name): ?string
    {
        return preg_match('/^kibarou-\d{8}-\d{6}-[0-9a-f]{6}\.zip$/', $name) && is_file($this->dir() . "/$name") ? $this->dir() . "/$name" : null;
    }

    /** Replaces the database content and restores the files. Destructive: the caller must have confirmed. */
    public function restore(string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \InvalidArgumentException('Archive illisible.');
        }
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $sql = $zip->getFromName('database.sql');
        if (($manifest['app'] ?? null) !== 'kibarou-cms' || $sql === false) {
            throw new \InvalidArgumentException('Ce fichier n\'est pas une sauvegarde KIBAROU CMS valide.');
        }
        // Validate every file entry before touching anything: a rejected archive changes nothing.
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '/') || in_array($name, ['database.sql', 'manifest.json'], true)) {
                continue;
            }
            if (!$this->safeEntry($name)) {
                throw new \InvalidArgumentException("Entrée refusée dans l'archive : $name");
            }
            $files[] = [$i, $name];
        }
        $pdo = $this->db->pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (explode("\n", $sql) as $line) {
                if (trim($line) !== '') {
                    $pdo->exec($line);
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
        foreach ($files as [$i, $name]) {
            $target = $this->root . '/' . substr($name, strlen('files/'));
            is_dir(dirname($target)) || mkdir(dirname($target), 0750, true);
            file_put_contents($target, $zip->getFromIndex($i));
        }
        $zip->close();
    }

    /** Zip-slip guard: only files/public/uploads/… and files/storage/private/…, no traversal, no odd characters. */
    private function safeEntry(string $name): bool
    {
        if (preg_match('#[\x00-\x1f\\\\]#', $name) || str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '//')) {
            return false;
        }
        foreach (self::FILE_DIRS as $rel) {
            if (str_starts_with($name, "files/$rel/") && !str_ends_with($name, '.htaccess') && !preg_match('/\.(php\d?|phtml|phar)$/i', $name)) {
                return true;
            }
        }
        return false;
    }

    private function dump(string $file): void
    {
        $pdo = $this->db->pdo();
        $h = fopen($file, 'w');
        fwrite($h, "SET NAMES utf8mb4\nSET FOREIGN_KEY_CHECKS = 0\n");
        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(\PDO::FETCH_NUM)[1];
            fwrite($h, "DROP TABLE IF EXISTS `$t`\n" . preg_replace('/\s*\R\s*/', ' ', $create) . "\n");
            $rows = $pdo->query("SELECT * FROM `$t`", \PDO::FETCH_NUM);
            $batch = [];
            $flush = function () use (&$batch, $h, $t) {
                if ($batch) {
                    fwrite($h, "INSERT INTO `$t` VALUES " . implode(',', $batch) . "\n");
                    $batch = [];
                }
            };
            foreach ($rows as $r) {
                // quote() escapes newlines, so every INSERT stays on a single line
                $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $r)) . ')';
                if (count($batch) >= self::ROWS_PER_INSERT) {
                    $flush();
                }
            }
            $flush();
        }
        fwrite($h, "SET FOREIGN_KEY_CHECKS = 1\n");
        fclose($h);
    }
}
