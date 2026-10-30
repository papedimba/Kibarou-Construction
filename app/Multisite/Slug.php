<?php
declare(strict_types=1);

namespace App\Multisite;

final class Slug
{
    public static function make(string $s): string
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
        return $s === '' ? 'item' : $s;
    }
}
