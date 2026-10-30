<?php
declare(strict_types=1);

namespace App\Repositories;

final class AppointmentRepository extends TenantRepository
{
    public const STATUSES = ['planned' => 'Prévue', 'done' => 'Effectuée', 'cancelled' => 'Annulée'];
    protected string $table = 'appointments';
    protected array $columns = ['lead_id', 'scheduled_at', 'note', 'status', 'created_by'];

    public function forLead(int $leadId): array
    {
        return $this->db->all('SELECT * FROM appointments WHERE site_id = ? AND lead_id = ? ORDER BY scheduled_at DESC', [$this->tenant->id(), $leadId]);
    }

    public function upcoming(int $limit = 100): array
    {
        return $this->db->all(
            'SELECT a.*, l.reference, l.first_name, l.last_name, l.phone, lp.title AS land_title FROM appointments a JOIN leads l ON l.site_id = a.site_id AND l.id = a.lead_id
             LEFT JOIN land_properties lp ON lp.site_id = l.site_id AND lp.id = l.land_id WHERE a.site_id = ? ORDER BY (a.status = \'planned\') DESC, a.scheduled_at LIMIT ' . (int) $limit,
            [$this->tenant->id()]
        );
    }
}
