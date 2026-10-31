<?php
declare(strict_types=1);

use App\Core\{App, Container, Env, Request};

// Static files the web server could not map to public/ by itself (web root = project root on a host without
// mod_rewrite). Only whitelisted types inside public/, never dotfiles or scripts.
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$file = $path !== '/' ? realpath(__DIR__ . $path) : false;
$types = ['css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'woff2' => 'font/woff2', 'ico' => 'image/x-icon'];
$ext = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : '';
if ($file && is_file($file) && isset($types[$ext]) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && !str_contains(substr($file, strlen(__DIR__)), '/.')) {
    header('Content-Type: ' . $types[$ext]);
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
Env::load($root . '/.env');

(new App(new Container(), $root))->handle(Request::fromGlobals())->send();
