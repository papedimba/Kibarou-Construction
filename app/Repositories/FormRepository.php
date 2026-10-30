<?php
declare(strict_types=1);

namespace App\Repositories;

final class FormRepository extends TenantRepository
{
    protected string $table = 'forms';
    protected array $columns = ['name', 'slug', 'fields', 'notify_email', 'success_message', 'is_active'];

    public function all(): array
    {
        return $this->db->all('SELECT f.*, (SELECT COUNT(*) FROM form_submissions s WHERE s.site_id = f.site_id AND s.form_id = f.id) AS submissions FROM forms f WHERE f.site_id = ? ORDER BY f.id DESC', [$this->tenant->id()]);
    }

    public function findActiveBySlug(string $slug): ?array
    {
        return $this->db->one('SELECT * FROM forms WHERE site_id = ? AND slug = ? AND is_active = 1', [$this->tenant->id(), $slug]);
    }
}
