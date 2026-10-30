<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Multisite\Slug;

final class DevelopmentRepository extends TenantRepository
{
    protected string $table = 'land_developments';
    protected array $columns = ['location_id', 'name', 'slug', 'description', 'is_published'];

    public function all(bool $publishedOnly = false): array
    {
        return $this->db->all(
            'SELECT d.*, (SELECT COUNT(*) FROM land_plots p WHERE p.site_id = d.site_id AND p.development_id = d.id) AS plots,
                    (SELECT COUNT(*) FROM land_plots p WHERE p.site_id = d.site_id AND p.development_id = d.id AND p.status = \'available\') AS available
             FROM land_developments d WHERE d.site_id = ?' . ($publishedOnly ? ' AND d.is_published = 1' : '') . ' ORDER BY d.name',
            [$this->tenant->id()]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->one('SELECT * FROM land_developments WHERE site_id = ? AND slug = ? AND is_published = 1', [$this->tenant->id(), $slug]);
    }

    public function uniqueSlug(string $name): string
    {
        $base = Slug::make($name);
        for ($slug = $base, $i = 2; $this->db->value('SELECT 1 FROM land_developments WHERE site_id = ? AND slug = ?', [$this->tenant->id(), $slug]); $i++) {
            $slug = "$base-$i";
        }
        return $slug;
    }
}
