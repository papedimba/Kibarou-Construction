<?php
// Dev only: php -S 127.0.0.1:8080 -t public bin/dev-router.php
// The built-in server 404s unknown paths with an extension (sitemap.xml); Apache's .htaccess does not.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . '/../public' . $path)) {
    return false;
}
require __DIR__ . '/../public/index.php';
