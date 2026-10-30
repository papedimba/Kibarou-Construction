<?php
declare(strict_types=1);

namespace Tests;

use App\Core\{App, Container, Request, Response};

trait HttpHelpers
{
    /** Fresh container per request, like a real PHP request; $_SESSION persists like a cookie would. */
    protected function call(string $method, string $path, string $host, array $post = [], bool $csrf = true, array $query = []): Response
    {
        // Real wiring (no injected services): catches autowiring regressions that unit-level setup would hide.
        $app = new App(new Container(), dirname(__DIR__));
        if ($method === 'POST' && $csrf) {
            $post['_csrf'] = $_SESSION['_csrf'] ??= bin2hex(random_bytes(8));
        }
        return $app->handle(new Request($method, $path, $host, $query, $post, ip: '10.0.0.1'));
    }

    protected function login(string $host, string $email, string $pass = 'correct-horse-battery', string $area = 'admin'): Response
    {
        return $this->call('POST', "/$area/login", $host, ['email' => $email, 'password' => $pass]);
    }
}
