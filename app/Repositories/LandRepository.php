<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Multisite\Slug;

final class LandRepository extends TenantRepository
{
    public const STATUSES = ['available', 'reserving', 'reserved', 'sold', 'unavailable'];
    protected string $table = 'land_properties';
    protected array $columns = ['category_id', 'location_id', 'reference', 'slug', 'title', 'description', 'area_m2', 'price_xof', 'status', 'is_published'];

    private const SORTS = [
        'price_asc' => 'l.price_xof ASC', 'price_desc' => 'l.price_xof DESC',
        'area_desc' => 'l.area_m2 DESC', 'recent' => 'l.created_at DESC, l.id DESC',
    ];

    public function categories(): array
    {
        return $this->db->all('SELECT * FROM land_categories WHERE site_id = ? ORDER BY name', [$this->tenant->id()]);
    }

    public function findBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        return $this->db->one(
            'SELECT l.*, c.name AS category_name FROM land_properties l LEFT JOIN land_categories c ON c.site_id = l.site_id AND c.id = l.category_id
             WHERE l.site_id = ? AND l.slug = ?' . ($publishedOnly ? ' AND l.is_published = 1' : ''),
            [$this->tenant->id(), $slug]
        );
    }

    /**
     * Server-side catalogue search. Filters: q, category (slug), min, max (FCFA), area_min, area_max, status, sort.
     * Public mode: published only, and sold/unavailable hidden unless a status is requested explicitly.
     * @return array{items:list<array>,total:int,pages:int,page:int}
     */
    public function search(array $f, bool $public, int $page = 1, int $perPage = 12): array
    {
        $w = ['l.site_id = ?'];
        $p = [$this->tenant->id()];
        if ($public) {
            $w[] = 'l.is_published = 1';
        }
        $status = $f['status'] ?? '';
        if (in_array($status, self::STATUSES, true)) {
            $w[] = 'l.status = ?';
            $p[] = $status;
        } elseif ($public) {
            $w[] = "l.status IN ('available','reserving','reserved')";
        }
        if (($f['q'] ?? '') !== '') {
            $w[] = '(l.title LIKE ? OR l.reference LIKE ?)';
            $like = '%' . addcslashes((string) $f['q'], '%_\\') . '%';
            array_push($p, $like, $like);
        }
        if (($f['category'] ?? '') !== '') {
            $w[] = 'c.slug = ?';
            $p[] = $f['category'];
        }
        foreach (['min' => ['l.price_xof >= ?'], 'max' => ['l.price_xof <= ?'], 'area_min' => ['l.area_m2 >= ?'], 'area_max' => ['l.area_m2 <= ?']] as $k => [$cond]) {
            if (isset($f[$k]) && ctype_digit((string) $f[$k])) {
                $w[] = $cond;
                $p[] = (int) $f[$k];
            }
        }
        $from = 'FROM land_properties l LEFT JOIN land_categories c ON c.site_id = l.site_id AND c.id = l.category_id WHERE ' . implode(' AND ', $w);
        $total = (int) $this->db->value("SELECT COUNT(*) $from", $p);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $order = self::SORTS[$f['sort'] ?? ''] ?? self::SORTS['recent'];
        $limit = 'LIMIT ' . (int) $perPage . ' OFFSET ' . (($page - 1) * $perPage);
        $items = $this->db->all("SELECT l.*, c.name AS category_name $from ORDER BY $order $limit", $p);
        return compact('items', 'total', 'pages', 'page');
    }

    public function countsByStatus(): array
    {
        $rows = $this->db->all('SELECT status, COUNT(*) n FROM land_properties WHERE site_id = ? GROUP BY status', [$this->tenant->id()]);
        return array_column($rows, 'n', 'status');
    }

    public function uniqueSlug(string $title): string
    {
        $base = Slug::make($title);
        $slug = $base;
        for ($i = 2; $this->db->value('SELECT 1 FROM land_properties WHERE site_id = ? AND slug = ?', [$this->tenant->id(), $slug]); $i++) {
            $slug = "$base-$i";
        }
        return $slug;
    }
}
