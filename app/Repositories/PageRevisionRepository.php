<?php
declare(strict_types=1);

namespace App\Repositories;

final class PageRevisionRepository extends TenantRepository
{
    protected string $table = 'page_revisions';
    protected array $columns = ['page_id', 'title', 'body', 'blocks', 'created_by'];

    public function forPage(int $pageId): array
    {
        return $this->db->all('SELECT r.id, r.title, r.created_at, u.name AS author FROM page_revisions r LEFT JOIN users u ON u.id = r.created_by WHERE r.site_id = ? AND r.page_id = ? ORDER BY r.id DESC LIMIT 50', [$this->tenant->id(), $pageId]);
    }
}
