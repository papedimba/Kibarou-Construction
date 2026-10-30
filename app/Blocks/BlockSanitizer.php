<?php
declare(strict_types=1);

namespace App\Blocks;

use App\Repositories\MediaRepository;

/**
 * Turns untrusted editor JSON into a clean block tree. Unknown block types and undeclared props are dropped,
 * every value is coerced to its field type, URLs are restricted, and image ids must belong to the current site.
 */
final class BlockSanitizer
{
    public const MAX_BLOCKS = 200;
    public const MAX_DEPTH = 4;
    private int $count = 0;
    private array $ids = [];
    private array $mediaOk = [];

    public function __construct(private MediaRepository $media)
    {
    }

    public static function validUrl(string $u): bool
    {
        return strlen($u) <= 500 && (bool) preg_match('~^(/(?!/)[^\s]*|https?://[^\s]+|mailto:[^\s]+|tel:\+?[0-9 .()-]+)$~', $u);
    }

    public function sanitize(mixed $blocks): array
    {
        $this->count = 0;
        $this->ids = [];
        return $this->blocks($blocks, 1);
    }

    private function blocks(mixed $in, int $depth): array
    {
        if (!is_array($in) || $depth > self::MAX_DEPTH) {
            return [];
        }
        $out = [];
        foreach ($in as $b) {
            if ($this->count >= self::MAX_BLOCKS) {
                break;
            }
            $def = is_array($b) && is_string($b['type'] ?? null) ? BlockRegistry::get($b['type']) : null;
            if (!$def) {
                continue;
            }
            $this->count++;
            $out[] = [
                'id' => $this->id($b['id'] ?? ''),
                'type' => $b['type'],
                'props' => $this->fields($def['fields'], is_array($b['props'] ?? null) ? $b['props'] : []),
                'children' => $def['container'] ? $this->blocks($b['children'] ?? [], $depth + 1) : [],
            ];
        }
        return $out;
    }

    private function id(mixed $id): string
    {
        $id = is_string($id) && preg_match('/^[a-z0-9]{6,12}$/', $id) ? $id : '';
        while ($id === '' || isset($this->ids[$id])) {
            $id = bin2hex(random_bytes(4));
        }
        return $this->ids[$id] = $id;
    }

    private function fields(array $defs, array $in): array
    {
        $out = [];
        foreach ($defs as $name => $d) {
            $out[$name] = $this->field($d, $in[$name] ?? null);
        }
        return $out;
    }

    private function text(mixed $v, int $max, bool $multiline): string
    {
        $v = is_scalar($v) ? (string) $v : '';
        $v = preg_replace($multiline ? '/[^\P{C}\n]+/u' : '/\p{C}+/u', '', str_replace("\r", '', $v)) ?? '';
        return mb_substr(trim($v), 0, $max);
    }

    private function field(array $d, mixed $v): mixed
    {
        switch ($d['type']) {
            case 'text':
                return $this->text($v, $d['max'] ?? 190, false);
            case 'textarea':
                return $this->text($v, $d['max'] ?? 2000, true);
            case 'number':
                return max($d['min'], min($d['max'], is_numeric($v) ? (int) $v : ($d['default'] ?? $d['min'])));
            case 'decimal':
                return is_numeric($v) && $v >= $d['min'] && $v <= $d['max'] ? (string) round((float) $v, 6) : '';
            case 'select':
                return is_scalar($v) && isset($d['options'][(string) $v]) ? (string) $v : ($d['default'] ?? (string) array_key_first($d['options']));
            case 'url':
                $u = $this->text($v, 500, false);
                return $u !== '' && self::validUrl($u) ? $u : '';
            case 'icon':
                return is_string($v) && in_array($v, Icons::names(), true) ? $v : '';
            case 'bool':
                return $v === true || $v === 1 || $v === '1' || $v === 'true';
            case 'image':
                return $this->mediaId($v);
            case 'list':
                $items = [];
                foreach (is_array($v) ? array_slice(array_values($v), 0, $d['max']) : [] as $item) {
                    $clean = $this->fields($d['item'], is_array($item) ? $item : []);
                    if (array_filter($clean, fn($x) => $x !== '' && $x !== 0 && $x !== false)) {
                        $items[] = $clean;
                    }
                }
                return $items;
        }
        return null;
    }

    /** Media ids come from the client: keep one only if it exists in this site's library. */
    private function mediaId(mixed $v): int
    {
        $id = is_numeric($v) ? (int) $v : 0;
        return $id > 0 && ($this->mediaOk[$id] ??= $this->media->find($id) !== null) ? $id : 0;
    }
}
