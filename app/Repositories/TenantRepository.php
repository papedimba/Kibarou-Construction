<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Multisite\TenantContext;

/**
 * Every query is scoped to the current tenant. site_id comes from TenantContext only:
 * it is stripped from caller data, so neither a form field nor a coding slip can cross tenants.
 */
abstract class TenantRepository
{
    protected string $table;
    /** @var list<string> writable columns (whitelist) */
    protected array $columns = [];

    public function __construct(protected Database $db, protected TenantContext $tenant)
    {
    }

    public function find(int $id): ?array
    {
        return $this->db->one("SELECT * FROM `{$this->table}` WHERE site_id = ? AND id = ?", [$this->tenant->id(), $id]);
    }

    public function insert(array $data): int
    {
        return $this->db->insert($this->table, $this->clean($data) + ['site_id' => $this->tenant->id()]);
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->clean($data);
        if (!$data) {
            return false;
        }
        $set = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $st = $this->db->run("UPDATE `{$this->table}` SET $set WHERE site_id = ? AND id = ?", [...array_values($data), $this->tenant->id(), $id]);
        return $st->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->run("DELETE FROM `{$this->table}` WHERE site_id = ? AND id = ?", [$this->tenant->id(), $id])->rowCount() > 0;
    }

    private function clean(array $data): array
    {
        return array_intersect_key($data, array_flip($this->columns));
    }
}
