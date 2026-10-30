<?php
declare(strict_types=1);

namespace App\Core;

final class Env
{
    public static function load(string $file): void
    {
        if (!is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            if (!isset($_ENV[$k])) {
                $_ENV[$k] = trim($v, " \t\"'");
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        $v = getenv($key);
        return $v === false ? $default : $v;
    }
}
