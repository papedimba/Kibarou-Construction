<?php
declare(strict_types=1);

namespace App\Blocks;

use App\Multisite\TenantContext;
use App\Repositories\{LandImageRepository, LandRepository, MediaRepository, ProjectImageRepository, ProjectRepository, ServiceRepository};

/**
 * Renders a sanitized block tree with fixed templates (app/Views/blocks/<type>.php). Templates only ever see
 * sanitized props plus this object, whose data accessors are all scoped to the current site.
 * In editor mode each block is wrapped so the editor can select, drag and drop it.
 */
final class BlockRenderer
{
    public bool $editor = false;
    private array $mediaCache = [];

    public function __construct(
        private TenantContext $tenant, private MediaRepository $media, private LandRepository $lands, private LandImageRepository $landImages,
        private ServiceRepository $services, private ProjectRepository $projects, private ProjectImageRepository $projectImages
    ) {
    }

    public function render(array $blocks, bool $editor = false): string
    {
        $this->editor = $editor;
        return $this->list($blocks);
    }

    /** Public HTML of a page row's published blocks, or null when it has none (the caller then shows the text body). */
    public function renderPage(array $page): ?string
    {
        $blocks = BlockStorage::decode($page['blocks'] ?? null);
        return $blocks ? '<div class="blocks">' . $this->render($blocks) . '</div>' : null;
    }

    private function list(array $blocks): string
    {
        return implode('', array_map(fn($b) => $this->one($b), $blocks));
    }

    private function one(array $b): string
    {
        $def = BlockRegistry::get($b['type'] ?? '');
        if (!$def) {
            return '';
        }
        $children = array_map(fn($c) => $this->one($c), $b['children'] ?? []);
        if ($def['container'] && !$children && $this->editor) {
            $children = ['<div class="blk-empty" data-drop-into="' . e($b['id']) . '">Déposez un bloc ici</div>'];
        }
        $html = $this->template($b['type'], $b['props'] ?? [], $children);
        return $this->editor ? '<div class="blk" data-block-id="' . e($b['id']) . '" data-type="' . e($b['type']) . '">' . $html . '</div>' : $html;
    }

    private function template(string $type, array $p, array $c): string
    {
        $r = $this;
        ob_start();
        require dirname(__DIR__) . "/Views/blocks/$type.php"; // $type is a registry key, never user text
        return (string) ob_get_clean();
    }

    // — data accessors for templates (all tenant-scoped through the repositories)

    public function media(mixed $id): ?array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        if (!array_key_exists($id, $this->mediaCache)) {
            $m = $this->media->find($id);
            $this->mediaCache[$id] = $m ? ['url' => '/uploads/' . $this->tenant->id() . '/' . basename($m['filename']), 'alt' => (string) $m['alt']] : null;
        }
        return $this->mediaCache[$id];
    }

    public function icon(string $name, int $size = 24): string
    {
        return Icons::svg($name, $size);
    }

    public function landByReference(string $ref): ?array
    {
        $found = $this->lands->search(['q' => $ref], true, 1, 50)['items'];
        foreach ($found as $l) {
            if ($l['reference'] === $ref) {
                return $l;
            }
        }
        return null;
    }

    /** @return array{items:list<array>,covers:array<int,string>} */
    public function catalog(array $filters, int $n): array
    {
        $items = $this->lands->search($filters, true, 1, $n)['items'];
        return ['items' => $items, 'covers' => $this->landImages->covers(array_map('intval', array_column($items, 'id')))];
    }

    public function covers(array $lands): array
    {
        return $this->landImages->covers(array_map('intval', array_column($lands, 'id')));
    }

    public function categories(): array
    {
        return $this->lands->categories();
    }

    /** @return list<array{url:string,alt:string}> photos of a published land */
    public function landPhotos(string $ref): array
    {
        $land = $this->landByReference($ref);
        return $land ? array_map(fn($i) => ['url' => '/uploads/' . $this->tenant->id() . '/' . basename($i['filename']), 'alt' => (string) ($i['alt'] ?: $land['title'])], $this->landImages->forOwner((int) $land['id'])) : [];
    }

    public function activeServices(int $n): array
    {
        return array_slice($this->services->all(true), 0, $n);
    }

    public function publishedProjects(int $n): array
    {
        $items = array_slice($this->projects->all(true), 0, $n);
        return array_map(fn($p) => $p + ['cover' => ($this->projectImages->covers([(int) $p['id']])[(int) $p['id']] ?? null)], $items);
    }

    public function project(string $slug): ?array
    {
        return $slug !== '' ? $this->projects->findBySlug($slug) : null;
    }

    /** @return list<array{url:string,alt:string}> */
    public function projectPhotos(array $project): array
    {
        return array_map(fn($i) => ['url' => '/uploads/' . $this->tenant->id() . '/' . basename($i['filename']), 'alt' => (string) ($i['alt'] ?: $project['name'])], $this->projectImages->forOwner((int) $project['id']));
    }

    public function siteId(): int
    {
        return $this->tenant->id();
    }

    /** Embed data built from an extracted video id, never from the raw URL. @return array{kind:string,src:string}|null */
    public function video(string $url): ?array
    {
        if (preg_match('~^https://(?:www\.)?(?:youtube\.com/watch\?(?:[^\s#]*&)?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return ['kind' => 'iframe', 'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1]];
        }
        if (preg_match('~^https://(?:www\.)?vimeo\.com/(\d{6,12})~', $url, $m)) {
            return ['kind' => 'iframe', 'src' => 'https://player.vimeo.com/video/' . $m[1]];
        }
        return preg_match('~^https://[^\s?#]+\.mp4(?:\?[^\s#]*)?$~i', $url) ? ['kind' => 'video', 'src' => $url] : null;
    }

    public function mapSrc(string $lat, string $lng): ?string
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        [$la, $ln, $d] = [(float) $lat, (float) $lng, 0.004];
        return sprintf('https://www.openstreetmap.org/export/embed.html?bbox=%F,%F,%F,%F&layer=mapnik&marker=%F,%F', $ln - $d, $la - $d, $ln + $d, $la + $d, $la, $ln);
    }
}
