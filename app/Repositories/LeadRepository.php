<?php
declare(strict_types=1);

namespace App\Repositories;

final class LeadRepository extends TenantRepository
{
    public const STATUSES = ['new' => 'Nouveau', 'to_contact' => 'À contacter', 'contacted' => 'Contact établi', 'visit_scheduled' => 'Visite programmée', 'negotiating' => 'En négociation', 'converted' => 'Converti', 'dropped' => 'Sans suite'];
    public const TYPES = ['info' => 'Demande d\'informations', 'visit' => 'Demande de visite', 'callback' => 'Demande de rappel', 'quote' => 'Demande de devis', 'partnership' => 'Partenariat', 'other' => 'Autre'];
    protected string $table = 'leads';
    protected array $columns = ['reference', 'type', 'first_name', 'last_name', 'phone', 'email', 'subject', 'message', 'preferred_date', 'land_id', 'service_id', 'status', 'assigned_to', 'consent_at', 'ip'];

    /** Filters: q, status, type, assigned (user id or "none"). @return array{items:list<array>,total:int,pages:int,page:int} */
    public function search(array $f, int $page = 1, int $perPage = 20): array
    {
        [$where, $p] = $this->where($f);
        $from = "FROM leads l LEFT JOIN users u ON u.id = l.assigned_to LEFT JOIN land_properties lp ON lp.site_id = l.site_id AND lp.id = l.land_id
                 LEFT JOIN construction_services s ON s.site_id = l.site_id AND s.id = l.service_id WHERE $where";
        $total = (int) $this->db->value("SELECT COUNT(*) $from", $p);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $items = $this->db->all(
            "SELECT l.*, u.name AS assignee, lp.title AS land_title, lp.reference AS land_ref, s.title AS service_title $from ORDER BY l.created_at DESC, l.id DESC LIMIT " . (int) $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $p
        );
        return compact('items', 'total', 'pages', 'page');
    }

    /** @return array{0:string,1:list<mixed>} */
    private function where(array $f): array
    {
        $w = ['l.site_id = ?'];
        $p = [$this->tenant->id()];
        if (isset(self::STATUSES[$f['status'] ?? ''])) {
            $w[] = 'l.status = ?';
            $p[] = $f['status'];
        }
        if (isset(self::TYPES[$f['type'] ?? ''])) {
            $w[] = 'l.type = ?';
            $p[] = $f['type'];
        }
        if (($f['assigned'] ?? '') === 'none') {
            $w[] = 'l.assigned_to IS NULL';
        } elseif (ctype_digit((string) ($f['assigned'] ?? ''))) {
            $w[] = 'l.assigned_to = ?';
            $p[] = (int) $f['assigned'];
        }
        if (trim((string) ($f['q'] ?? '')) !== '') {
            $like = '%' . addcslashes(trim((string) $f['q']), '%_\\') . '%';
            $w[] = '(l.reference LIKE ? OR l.first_name LIKE ? OR l.last_name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)';
            array_push($p, $like, $like, $like, $like, $like);
        }
        return [implode(' AND ', $w), $p];
    }

    public function detail(int $id): ?array
    {
        return $this->db->one(
            'SELECT l.*, u.name AS assignee, lp.title AS land_title, lp.reference AS land_ref, lp.slug AS land_slug, s.title AS service_title
             FROM leads l LEFT JOIN users u ON u.id = l.assigned_to LEFT JOIN land_properties lp ON lp.site_id = l.site_id AND lp.id = l.land_id
             LEFT JOIN construction_services s ON s.site_id = l.site_id AND s.id = l.service_id WHERE l.site_id = ? AND l.id = ?',
            [$this->tenant->id(), $id]
        );
    }

    public function counts(): array
    {
        $by = array_column($this->db->all('SELECT status, COUNT(*) n FROM leads WHERE site_id = ? GROUP BY status', [$this->tenant->id()]), 'n', 'status');
        $quotes = (int) $this->db->value("SELECT COUNT(*) FROM leads WHERE site_id = ? AND type = 'quote'", [$this->tenant->id()]);
        return ['total' => array_sum($by), 'pending' => (int) (($by['new'] ?? 0) + ($by['to_contact'] ?? 0)), 'quotes' => $quotes];
    }

    /** Members of the current site (any role): the only people a lead can be assigned to. */
    public function assignableUsers(): array
    {
        return $this->db->all('SELECT DISTINCT u.id, u.name FROM site_members m JOIN users u ON u.id = m.user_id WHERE m.site_id = ? AND u.status = \'active\' ORDER BY u.name', [$this->tenant->id()]);
    }

    /** Requests recently made from one IP, across leads and custom forms (antispam rate limit). */
    public function recentFromIp(string $ip, int $minutes = 60): int
    {
        $sid = $this->tenant->id();
        $m = (int) $minutes;
        return (int) $this->db->value("SELECT (SELECT COUNT(*) FROM leads WHERE site_id = ? AND ip = ? AND created_at > NOW() - INTERVAL $m MINUTE) + (SELECT COUNT(*) FROM form_submissions WHERE site_id = ? AND ip = ? AND created_at > NOW() - INTERVAL $m MINUTE)", [$sid, $ip, $sid, $ip]);
    }
}
