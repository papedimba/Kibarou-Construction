<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\{Database, Env, Migrator};
use App\Multisite\TenantProvisioner;

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
    case 'site:create': // site:create <slug> <name> <host> <adminEmail> <adminName> <password>
        [, , $slug, $name, $host, $email, $adminName, $pass] = $argv;
        $id = (new TenantProvisioner($db))->create($name, $slug, $host, $email, $adminName, $pass);
        echo "Site #$id created\n";
        break;
    default:
        echo "Usage: php bin/console.php migrate | superadmin <email> <password> <name> | site:create <slug> <name> <host> <email> <adminName> <password>\n";
}
