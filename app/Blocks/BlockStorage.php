<?php
declare(strict_types=1);

namespace App\Blocks;

/** Versioned JSON envelope: {"v":1,"blocks":[…]}. A future schema change bumps VERSION and adds a migration branch in decode(). */
final class BlockStorage
{
    public const VERSION = 1;

    public static function encode(array $blocks): string
    {
        return json_encode(['v' => self::VERSION, 'blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @return list<array> empty for null, corrupt, or unknown-version data (the page then falls back to its text body) */
    public static function decode(?string $json): array
    {
        $d = $json ? json_decode($json, true) : null;
        return is_array($d) && ($d['v'] ?? null) === self::VERSION && is_array($d['blocks'] ?? null) ? $d['blocks'] : [];
    }
}
