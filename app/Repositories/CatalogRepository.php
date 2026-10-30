<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Multisite\Slug;

/** Services (construction_services) and projects: same slug logic, different columns. */
abstract class CatalogRepository extends TenantRepository
{
    protected string $titleCol = 'title';

    public function all(bool $publicOnly = false): array
    {
        return $this->db->all("SELECT * FROM `{$this->table}` WHERE site_id = ?" . ($publicOnly ? " AND {$this->flag()} = 1" : '') . ' ORDER BY id DESC', [$this->tenant->id()]);
    }

    public function findBySlug(string $slug, bool $publicOnly = true): ?array
    {
        return $this->db->one("SELECT * FROM `{$this->table}` WHERE site_id = ? AND slug = ?" . ($publicOnly ? " AND {$this->flag()} = 1" : ''), [$this->tenant->id(), $slug]);
    }

    public function uniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = Slug::make($title);
        for ($slug = $base, $i = 2; $this->db->value("SELECT 1 FROM `{$this->table}` WHERE site_id = ? AND slug = ? AND id <> ?", [$this->tenant->id(), $slug, $exceptId ?? 0]); $i++) {
            $slug = "$base-$i";
        }
        return $slug;
    }

    abstract protected function flag(): string;
}
