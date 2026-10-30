<?php
declare(strict_types=1);

namespace App\Repositories;

final class RedirectRepository extends TenantRepository
{
    protected string $table = 'redirects';
    protected array $columns = ['from_path', 'to_url'];

    public function target(string $path): ?string
    {
        return $this->db->value('SELECT to_url FROM redirects WHERE site_id = ? AND from_path = ?', [$this->tenant->id(), $path]);
    }

    public function all(): array
    {
        return $this->db->all('SELECT * FROM redirects WHERE site_id = ? ORDER BY id DESC', [$this->tenant->id()]);
    }
}
