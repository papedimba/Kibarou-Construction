<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Backup;
use Tests\{DbTestCase, HttpHelpers};

final class BackupTest extends DbTestCase
{
    use HttpHelpers;

    private string $root;
    private Backup $backup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/kib-backup-' . bin2hex(random_bytes(4));
        mkdir("$this->root/public/uploads/1", 0755, true);
        mkdir("$this->root/storage/private/1", 0755, true);
        $this->backup = new Backup($this->db, $this->root);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
        foreach (glob(dirname(__DIR__, 2) . '/storage/backups/kibarou-*.zip') ?: [] as $f) { // created by the HTTP tests
            unlink($f);
        }
    }

    private function entries(string $zip): array
    {
        $z = new \ZipArchive();
        $z->open($zip);
        return array_map(fn($i) => $z->getNameIndex($i), range(0, $z->numFiles - 1));
    }

    public function testRoundTripRestoresRowsFilesAndAwkwardValues(): void
    {
        $a = $this->makeSite('alpha', 'alpha.test');
        $awkward = "Ligne 1\nLigne 2 'quote' \"dq\" \\ back; DROP TABLE x;-- é 🏗 \t fin";
        $this->db->insert('pages', ['site_id' => $a, 'slug' => 'p', 'title' => 'T', 'body' => $awkward, 'status' => 'draft']);
        $this->makeLand($a, 'KEEP-1');
        file_put_contents("$this->root/public/uploads/1/pic.png", 'PNGDATA');
        file_put_contents("$this->root/storage/private/1/doc.pdf", '%PDF-secret');
        $zip = $this->backup->create();
        $this->assertSame('0640', substr(sprintf('%o', fileperms($zip)), -4));
        $this->assertEqualsCanonicalizing(['database.sql', 'manifest.json', 'files/public/uploads/1/pic.png', 'files/storage/private/1/doc.pdf'], $this->entries($zip));

        // disaster
        $this->db->run('DELETE FROM land_properties');
        $this->db->run("UPDATE pages SET body = 'changed'");
        $this->db->insert('pages', ['site_id' => $a, 'slug' => 'junk', 'title' => 'Junk', 'status' => 'draft']);
        unlink("$this->root/public/uploads/1/pic.png");
        file_put_contents("$this->root/storage/private/1/doc.pdf", 'tampered');

        $this->backup->restore($zip);
        $this->assertSame($awkward, $this->db->value("SELECT body FROM pages WHERE slug = 'p'"));
        $this->assertNull($this->db->value("SELECT id FROM pages WHERE slug = 'junk'"));
        $this->assertSame('KEEP-1', $this->db->value('SELECT reference FROM land_properties'));
        $this->assertSame('PNGDATA', file_get_contents("$this->root/public/uploads/1/pic.png"));
        $this->assertSame('%PDF-secret', file_get_contents("$this->root/storage/private/1/doc.pdf"));
        $this->assertSame(1, (int) $this->db->value('SELECT is_super_admin = 0 FROM users WHERE email = ?', ['admin@alpha.test'])); // users survive
        $this->assertGreaterThan(0, (int) $this->db->value('SELECT COUNT(*) FROM role_permissions')); // seed tables too
    }

    public function testMaliciousArchiveIsRejectedBeforeAnythingChanges(): void
    {
        $a = $this->makeSite('alpha', 'alpha.test');
        $this->db->insert('pages', ['site_id' => $a, 'slug' => 'safe', 'title' => 'Safe', 'status' => 'draft']);
        $valid = $this->backup->create();
        foreach (['../evil.txt', 'files/public/uploads/../../evil.txt', 'files/public/uploads/a.php', 'files/public/uploads/.htaccess', '/etc/cron.d/x', 'files/storage/private/..\\x', 'files/other/x.txt', "files/public/uploads/a\nb.png"] as $bad) {
            $evil = $this->root . '/evil.zip';
            @unlink($evil);
            copy($valid, $evil);
            $z = new \ZipArchive();
            $z->open($evil);
            $z->addFromString($bad, 'x');
            $z->close();
            $this->db->run("UPDATE pages SET title = 'Marker'");
            try {
                $this->backup->restore($evil);
                $this->fail("accepted $bad");
            } catch (\InvalidArgumentException) {
            }
            $this->assertSame('Marker', $this->db->value("SELECT title FROM pages WHERE slug = 'safe'"), "DB touched for $bad");
        }
        $this->assertFileDoesNotExist(dirname($this->root) . '/evil.txt');
        $this->assertFileDoesNotExist("$this->root/evil.txt");
    }

    public function testNonBackupFilesAreRefused(): void
    {
        $z = new \ZipArchive();
        $f = "$this->root/random.zip";
        $z->open($f, \ZipArchive::CREATE);
        $z->addFromString('hello.txt', 'hi');
        $z->close();
        $this->expectException(\InvalidArgumentException::class);
        $this->backup->restore($f);
    }

    public function testPruneKeepsNewestAndPathAcceptsOnlyOwnNames(): void
    {
        $dir = $this->backup->dir();
        foreach (['20240101-000001', '20240102-000001', '20240103-000001'] as $d) {
            file_put_contents("$dir/kibarou-$d-abcdef.zip", 'x');
        }
        $this->assertSame(2, $this->backup->prune(1));
        $this->assertSame(['kibarou-20240103-000001-abcdef.zip'], array_column($this->backup->list(), 'name'));
        $this->assertNotNull($this->backup->path('kibarou-20240103-000001-abcdef.zip'));
        foreach (['../x.zip', 'kibarou-x.zip', 'kibarou-20240103-000001-abcdef.zip/../../x', 'database.sql', ''] as $bad) {
            $this->assertNull($this->backup->path($bad), $bad);
        }
    }

    public function testPlatformBackupsAreSuperAdminOnlyAndDownloadsAreScoped(): void
    {
        $this->makeSite('alpha', 'alpha.test');
        $this->db->insert('users', ['email' => 'root@cms.test', 'name' => 'Root', 'is_super_admin' => 1, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->assertSame(404, $this->call('GET', '/platform/backups', 'alpha.test')->status); // not on tenant hosts
        $this->login('alpha.test', 'admin@alpha.test');
        $this->assertSame(404, $this->call('GET', '/platform/backups', 'alpha.test')->status);
        $_SESSION = [];
        $this->login('platform.test', 'admin@alpha.test', area: 'platform');
        $this->assertSame(302, $this->call('GET', '/platform/backups', 'platform.test')->status); // tenant admin is not a super admin
        $_SESSION = [];
        $this->login('platform.test', 'root@cms.test', area: 'platform');
        $this->assertSame(200, $this->call('GET', '/platform/backups', 'platform.test')->status);
        $this->assertSame(302, $this->call('POST', '/platform/backups', 'platform.test')->status);
        $name = basename((string) glob(dirname(__DIR__, 2) . '/storage/backups/kibarou-*.zip')[0]);
        $res = $this->call('GET', "/platform/backups/$name", 'platform.test');
        $this->assertSame('application/zip', $res->headers['Content-Type']);
        $this->assertStringContainsString('attachment', $res->headers['Content-Disposition']);
        $this->assertSame('PK', substr($res->body, 0, 2));
        $this->assertSame(404, $this->call('GET', '/platform/backups/..%2f..%2f.env', 'platform.test')->status);
        $this->assertSame(404, $this->call('GET', '/platform/backups/nope.zip', 'platform.test')->status);
    }
}
