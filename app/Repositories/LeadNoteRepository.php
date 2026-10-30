<?php
declare(strict_types=1);

namespace App\Repositories;

final class LeadNoteRepository extends TenantRepository
{
    protected string $table = 'lead_notes';
    protected array $columns = ['lead_id', 'user_id', 'body'];

    public function forLead(int $leadId): array
    {
        return $this->db->all('SELECT n.*, u.name AS author FROM lead_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.site_id = ? AND n.lead_id = ? ORDER BY n.id DESC', [$this->tenant->id(), $leadId]);
    }
}
