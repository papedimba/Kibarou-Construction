<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Multisite\TenantContext;
use App\Repositories\{AppointmentRepository, LeadHistoryRepository, LeadNoteRepository, LeadRepository, SettingsRepository};

/** Every lead mutation goes through here so that each change leaves a history entry. Callers load the lead through the tenant repository first. */
final class LeadService
{
    public function __construct(
        private Database $db, private TenantContext $tenant, private LeadRepository $leads, private LeadHistoryRepository $history,
        private LeadNoteRepository $notes, private AppointmentRepository $appointments, private SettingsRepository $settings, private Mailer $mailer
    ) {
    }

    /** @param array $d validated fields. @return array{id:int,reference:string} */
    public function create(array $d, ?array $land, ?array $service, string $ip): array
    {
        $created = $this->db->transaction(function () use ($d, $land, $service, $ip) {
            $id = $this->leads->insert($d + [
                'reference' => 'T' . bin2hex(random_bytes(8)), 'land_id' => $land['id'] ?? null, 'service_id' => $service['id'] ?? null,
                'status' => 'new', 'consent_at' => date('Y-m-d H:i:s'), 'ip' => $ip,
            ]);
            $ref = 'LD-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            $this->leads->update($id, ['reference' => $ref]);
            $this->history->log($id, null, 'created', LeadRepository::TYPES[$d['type']] ?? $d['type']);
            return ['id' => $id, 'reference' => $ref];
        });
        $to = $this->settings->all()['email'] ?? '';
        if ($to !== '') { // a failed notification must never lose the lead: it is already saved
            $about = $land ? "Terrain {$land['reference']} — {$land['title']}" : ($service ? "Service : {$service['title']}" : '—');
            $this->mailer->send(
                $to, "[{$this->tenant->site()['name']}] Nouvelle demande {$created['reference']}",
                "Type : " . (LeadRepository::TYPES[$d['type']] ?? $d['type']) . "\nNom : {$d['first_name']} {$d['last_name']}\nTéléphone : {$d['phone']}\nEmail : " . ($d['email'] ?? '—') . "\nObjet : $about\n\n" . ($d['message'] ?? ''),
                $d['email'] ?? null
            );
        }
        return $created;
    }

    public function setStatus(array $lead, string $status, ?int $userId): bool
    {
        if (!isset(LeadRepository::STATUSES[$status]) || $lead['status'] === $status) {
            return false;
        }
        $this->leads->update((int) $lead['id'], ['status' => $status]);
        $this->history->log((int) $lead['id'], $userId, 'status', LeadRepository::STATUSES[$lead['status']] . ' → ' . LeadRepository::STATUSES[$status]);
        return true;
    }

    public function assign(array $lead, ?int $assignee, ?int $userId): bool
    {
        $ids = array_map('intval', array_column($this->leads->assignableUsers(), 'id'));
        if ($assignee !== null && !in_array($assignee, $ids, true)) {
            return false; // only members of this site can own a lead
        }
        $this->db->run('UPDATE leads SET assigned_to = ? WHERE site_id = ? AND id = ?', [$assignee, $this->tenant->id(), $lead['id']]);
        $name = $assignee ? ($this->db->value('SELECT name FROM users WHERE id = ?', [$assignee]) ?? '') : 'personne';
        $this->history->log((int) $lead['id'], $userId, 'assign', "Responsable : $name");
        return true;
    }

    public function addNote(array $lead, string $body, ?int $userId): bool
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 2000) {
            return false;
        }
        $this->notes->insert(['lead_id' => $lead['id'], 'user_id' => $userId, 'body' => $body]);
        $this->history->log((int) $lead['id'], $userId, 'note', mb_substr($body, 0, 80));
        return true;
    }

    /** @param string $when "YYYY-MM-DDTHH:MM" (datetime-local). @return string|null error */
    public function schedule(array $lead, string $when, string $note, ?int $userId): ?string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $when);
        if (!$dt || $dt->format('Y-m-d\TH:i') !== $when) {
            return 'Date et heure invalides.';
        }
        $this->appointments->insert(['lead_id' => $lead['id'], 'scheduled_at' => $dt->format('Y-m-d H:i:s'), 'note' => mb_substr(trim($note), 0, 300) ?: null, 'created_by' => $userId]);
        $this->history->log((int) $lead['id'], $userId, 'visit', 'Visite le ' . $dt->format('d/m/Y H:i'));
        if (in_array($lead['status'], ['new', 'to_contact', 'contacted'], true)) {
            $this->setStatus($lead, 'visit_scheduled', $userId);
        }
        return null;
    }
}
