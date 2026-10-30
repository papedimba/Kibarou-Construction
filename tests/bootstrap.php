<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\{Database, Env, Migrator};

// Tests never touch the dev database.
$_ENV['DB_NAME'] = 'kibarou_test';
$_ENV['PLATFORM_HOST'] = 'platform.test';
$_ENV['PLATFORM_BASE_DOMAIN'] = 'cms.test';
$_ENV['APP_DEBUG'] = '1';
Env::load(dirname(__DIR__) . '/.env');

$db = new Database();
$db->run('SET FOREIGN_KEY_CHECKS = 0');
foreach ($db->all('SHOW TABLES') as $row) {
    $db->run('DROP TABLE `' . array_values($row)[0] . '`');
}
$db->run('SET FOREIGN_KEY_CHECKS = 1');
(new Migrator($db, dirname(__DIR__) . '/database/migrations'))->run();
