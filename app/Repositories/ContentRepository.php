<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Multisite\Slug;

/** Shared by pages and posts (same shape, different table). */
abstract class ContentRepository extends TenantRepository
{
    /** Slugs that routes already own; a page with one of these would be unreachable or shadow a feature. */
    public const RESERVED = ['admin', 'platform', 'terrains', 'actualites', 'assets', 'uploads', 'sitemap.xml', 'robots.txt'];
    protected array $columns = ['slug', 'title', 'body', 'meta_title', 'meta_description', 'status', 'published_at'];

    public function paginate(bool $publishedOnly, int $page = 1, int $perPage = 20): array
    {
        $where = 'site_id = ?' . ($publishedOnly ? " AND status = 'published'" : '');
        $p = [$this->tenant->id()];
        $total = (int) $this->db->value("SELECT COUNT(*) FROM `{$this->table}` WHERE $where", $p);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $items = $this->db->all("SELECT * FROM `{$this->table}` WHERE $where ORDER BY COALESCE(published_at, created_at) DESC, id DESC LIMIT " . (int) $perPage . ' OFFSET ' . (($page - 1) * $perPage), $p);
        return compact('items', 'total', 'pages', 'page');
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->db->one("SELECT * FROM `{$this->table}` WHERE site_id = ? AND slug = ? AND status = 'published'", [$this->tenant->id(), $slug]);
    }

    public function slugTaken(string $slug, ?int $exceptId = null): bool
    {
        return (bool) $this->db->value("SELECT 1 FROM `{$this->table}` WHERE site_id = ? AND slug = ? AND id <> ?", [$this->tenant->id(), $slug, $exceptId ?? 0]);
    }

    public function slugFor(string $wanted, string $title): string
    {
        return Slug::make($wanted !== '' ? $wanted : $title);
    }

    public function allPublishedSlugs(): array
    {
        return $this->db->all("SELECT slug, updated_at FROM `{$this->table}` WHERE site_id = ? AND status = 'published' ORDER BY id", [$this->tenant->id()]);
    }
}
