<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\{Database, Env, Migrator};
use App\Multisite\TenantProvisioner;
use App\Services\{Backup, Installer};

Env::load(__DIR__ . '/../.env');
$db = new Database();
[$_, $cmd] = $argv + [null, 'help'];

switch ($cmd) {
    case 'migrate':
        $done = (new Migrator($db, __DIR__ . '/../database/migrations'))->run();
        echo $done ? 'Applied: ' . implode(', ', $done) : 'Nothing to migrate', "\n";
        break;
    case 'superadmin': // superadmin <email> <password> <name>
        [, , $email, $pass, $name] = $argv + [null, null, null, null, 'Super Admin'];
        $db->insert('users', ['email' => $email, 'name' => $name, 'is_super_admin' => 1,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT)]);
        echo "Super admin created\n";
        break;
    case 'site:create': // setting <slug> <key> <value> | site:create <slug> <name> <host> <adminEmail> <adminName> <password>
        [, , $slug, $name, $host, $email, $adminName, $pass] = $argv;
        $id = (new TenantProvisioner($db))->create($name, $slug, $host, $email, $adminName, $pass);
        echo "Site #$id created\n";
        break;
    case 'setting': // setting <siteSlug> <key> <value>
        [, , $slug, $key, $value] = $argv;
        $siteId = $db->value('SELECT id FROM sites WHERE slug = ?', [$slug]) ?? throw new RuntimeException("Unknown site $slug");
        if (!in_array($key, \App\Repositories\SettingsRepository::KEYS, true)) {
            throw new RuntimeException("Unknown setting $key");
        }
        $db->run('INSERT INTO settings (site_id, `key`, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$siteId, $key, $value]);
        echo "Set $key for $slug\n";
        break;
    case 'backup': // backup [--keep=N]
        $zip = (new Backup($db, dirname(__DIR__)))->create();
        echo "Backup: $zip\n";
        foreach ($argv as $a) {
            if (preg_match('/^--keep=(\d+)$/', $a, $m)) {
                echo 'Pruned: ' . (new Backup($db, dirname(__DIR__)))->prune((int) $m[1]) . "\n";
            }
        }
        break;
    case 'restore': // restore <zip> --yes   (replaces the whole database and restores files)
        if (!in_array('--yes', $argv, true) || !isset($argv[2]) || !is_file($argv[2])) {
            exit("Usage: php bin/console.php restore <archive.zip> --yes   (REPLACES all current data)\n");
        }
        (new Backup($db, dirname(__DIR__)))->restore($argv[2]);
        echo "Restored from {$argv[2]}\n";
        break;
    case 'site:purge': // site:purge <slug> --yes   (irreversible; the site must already be marked deleted)
        $slug = $argv[2] ?? '';
        $id = $db->value('SELECT id FROM sites WHERE slug = ?', [$slug]);
        if (!$id || !in_array('--yes', $argv, true)) {
            exit("Usage: php bin/console.php site:purge <slug> --yes   (IRREVERSIBLE; mark the site deleted first in /platform)\n");
        }
        (new TenantProvisioner($db))->purge((int) $id, dirname(__DIR__));
        echo "Site $slug purged\n";
        break;
    case 'doctor': // post-deployment checks
        $ok = true;
        foreach ((new Installer(dirname(__DIR__)))->requirements() as [$label, $pass, $detail]) {
            echo ($pass ? '[ok]   ' : '[FAIL] ') . "$label — $detail\n";
            $ok = $ok && $pass;
        }
        $checks = [
            'APP_ENV=production' => Env::get('APP_ENV') === 'production', 'APP_DEBUG=0' => Env::get('APP_DEBUG') !== '1',
            'APP_KEY défini (32+ caractères)' => strlen((string) Env::get('APP_KEY')) >= 32, 'PLATFORM_HOST défini' => (bool) Env::get('PLATFORM_HOST'),
            '.env non lisible par tous' => !is_file(dirname(__DIR__) . '/.env') || (fileperms(dirname(__DIR__) . '/.env') & 0004) === 0,
            'public/install.php supprimé' => !is_file(dirname(__DIR__) . '/public/install.php'),
        ];
        foreach ($checks as $label => $pass) {
            echo ($pass ? '[ok]   ' : '[WARN] ') . "$label\n";
        }
        echo $ok ? "Prérequis satisfaits.\n" : "Des prérequis sont manquants.\n";
        exit($ok ? 0 : 1);
    default:
        echo "Usage: php bin/console.php migrate | backup [--keep=N] | restore <zip> --yes | doctor | superadmin <email> <password> <name> | site:create <slug> <name> <host> <email> <adminName> <password>\n";
}
