<?php
declare(strict_types=1);

use App\Core\{App, Container, Env, Request};

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
Env::load($root . '/.env');

(new App(new Container(), $root))->handle(Request::fromGlobals())->send();
