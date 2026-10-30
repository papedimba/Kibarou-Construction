<?php
declare(strict_types=1);

namespace App\Blocks;

/** Lucide icons (ISC licence, see icons/LICENSE) read from vetted local files; the name is checked against the directory listing. */
final class Icons
{
    /** @return list<string> */
    public static function names(): array
    {
        static $names;
        return $names ??= array_map(fn($f) => basename($f, '.svg'), glob(__DIR__ . '/icons/*.svg') ?: []);
    }

    public static function svg(string $name, int $size = 24): string
    {
        if (!in_array($name, self::names(), true)) {
            return '';
        }
        $svg = (string) file_get_contents(__DIR__ . "/icons/$name.svg");
        $svg = preg_replace('/<!--.*?-->/s', '', $svg);
        return preg_replace('/\b(width|height)="24"/', '$1="' . $size . '"', $svg, 2) . '';
    }
}
