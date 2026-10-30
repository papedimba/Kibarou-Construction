<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function start(bool $secure): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            $dir = dirname(__DIR__, 2) . '/storage/sessions';
            if (is_writable($dir)) {
                session_save_path($dir);
            }
            session_name('kib_sid');
            // No "domain": the cookie is bound to the exact host, so tenants never share a session.
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $secure, 'path' => '/']);
            ini_set('session.use_strict_mode', '1');
            session_start();
        }
        $_SESSION ??= [];
    }

    public function get(string $k, mixed $d = null): mixed
    {
        return $_SESSION[$k] ?? $d;
    }

    public function set(string $k, mixed $v): void
    {
        $_SESSION[$k] = $v;
    }

    public function forget(string $k): void
    {
        unset($_SESSION[$k]);
    }

    public function flash(?string $msg = null): ?string
    {
        if ($msg !== null) {
            $_SESSION['_flash'] = $msg;
            return null;
        }
        $m = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return $m;
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function csrf(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public function checkCsrf(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
}
