<?php
declare(strict_types=1);

namespace App\Repositories;

final class SubmissionRepository extends TenantRepository
{
    protected string $table = 'form_submissions';
    protected array $columns = ['form_id', 'data', 'ip'];

    public function forForm(int $formId, int $limit = 200): array
    {
        return $this->db->all('SELECT * FROM form_submissions WHERE site_id = ? AND form_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$this->tenant->id(), $formId]);
    }
}
