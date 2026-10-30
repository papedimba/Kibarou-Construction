<?php
declare(strict_types=1);

namespace App\Repositories;

final class LeadHistoryRepository extends TenantRepository
{
    protected string $table = 'lead_history';
    protected array $columns = ['lead_id', 'user_id', 'action', 'detail'];

    public function log(int $leadId, ?int $userId, string $action, ?string $detail = null): void
    {
        $this->insert(['lead_id' => $leadId, 'user_id' => $userId, 'action' => $action, 'detail' => $detail !== null ? mb_substr($detail, 0, 255) : null]);
    }

    public function forLead(int $leadId): array
    {
        return $this->db->all('SELECT h.*, u.name AS author FROM lead_history h LEFT JOIN users u ON u.id = h.user_id WHERE h.site_id = ? AND h.lead_id = ? ORDER BY h.id DESC', [$this->tenant->id(), $leadId]);
    }
}
