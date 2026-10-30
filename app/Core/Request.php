<?php
declare(strict_types=1);

namespace App\Core;

use App\Multisite\DomainManager;

final class Request
{
    public readonly string $host;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        string $host,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $cookies = [],
        public readonly bool $secure = false,
        public readonly string $ip = '0.0.0.0',
        public readonly array $files = [],
    ) {
        $this->host = DomainManager::normalize($host);
    }

    public static function fromGlobals(): self
    {
        $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $path = '/' . trim(preg_replace('#/+#', '/', $path), '/');
        $post = $_POST;
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $raw = (string) file_get_contents('php://input', false, null, 0, 1_000_001);
            $json = strlen($raw) <= 1_000_000 ? json_decode($raw, true) : null; // oversized/invalid body -> empty, never partial
            $post = is_array($json) ? $json : [];
        }
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_SERVER['HTTP_HOST'] ?? '',
            $_GET,
            $post,
            $_COOKIE,
            ($_SERVER['HTTPS'] ?? 'off') !== 'off',
            // shortcut: X-Forwarded-For ignored (spoofable); read it only behind a trusted proxy
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_FILES,
        );
    }

    public function url(): string
    {
        return ($this->secure ? 'https' : 'http') . '://' . $this->host . $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }
}
