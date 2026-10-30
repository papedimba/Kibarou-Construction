<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{AppointmentRepository, LeadHistoryRepository, LeadNoteRepository, LeadRepository};
use App\Services\{AuthService, LeadService};

final class LeadController
{
    public function __construct(
        private Page $page, private LeadRepository $leads, private LeadService $service, private LeadNoteRepository $notes,
        private LeadHistoryRepository $history, private AppointmentRepository $appointments, private AuthService $auth
    ) {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/leads', [
            'r' => $this->leads->search($req->query, (int) $req->input('page', 1)), 'f' => $req->query, 'users' => $this->leads->assignableUsers(),
            'statuses' => LeadRepository::STATUSES, 'types' => LeadRepository::TYPES, 'title' => 'Prospects',
        ]);
    }

    public function export(Request $req, array $p): Response
    {
        $h = fopen('php://temp', 'w+');
        fwrite($h, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
        fputcsv($h, ['Référence', 'Date', 'Type', 'Statut', 'Nom', 'Prénom', 'Téléphone', 'Email', 'Objet', 'Responsable', 'Message'], ';', '"', '');
        // shortcut: first 5000 matching leads only; paginate the export beyond that
        foreach ($this->leads->search($req->query, 1, 5000)['items'] as $l) {
            fputcsv($h, array_map([self::class, 'cell'], [
                $l['reference'], $l['created_at'], LeadRepository::TYPES[$l['type']] ?? $l['type'], LeadRepository::STATUSES[$l['status']] ?? $l['status'],
                $l['last_name'], $l['first_name'], $l['phone'], $l['email'], $l['subject'], $l['assignee'], $l['message'],
            ]), ';', '"', '');
        }
        rewind($h);
        return new Response((string) stream_get_contents($h), 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="prospects-' . date('Ymd') . '.csv"']);
    }

    /** Neutralises spreadsheet formulas (=, +, -, @) in user-supplied cells. */
    public static function cell(?string $v): string
    {
        $v = (string) $v;
        return $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
    }

    public function show(Request $req, array $p): Response
    {
        $lead = $this->leads->detail((int) $p['id']);
        return $lead ? $this->page->render('admin/lead', [
            'lead' => $lead, 'notes' => $this->notes->forLead((int) $lead['id']), 'history' => $this->history->forLead((int) $lead['id']),
            'appointments' => $this->appointments->forLead((int) $lead['id']), 'users' => $this->leads->assignableUsers(),
            'statuses' => LeadRepository::STATUSES, 'types' => LeadRepository::TYPES, 'title' => $lead['reference'],
        ]) : $this->notFound();
    }

    public function status(Request $req, array $p): Response
    {
        return $this->act($p, fn($lead) => $this->service->setStatus($lead, (string) $req->input('status'), $this->uid()));
    }

    public function assign(Request $req, array $p): Response
    {
        $to = (string) $req->input('assigned_to', '');
        return $this->act($p, fn($lead) => $this->service->assign($lead, $to === '' ? null : (int) $to, $this->uid()));
    }

    public function note(Request $req, array $p): Response
    {
        return $this->act($p, fn($lead) => $this->service->addNote($lead, (string) $req->input('body', ''), $this->uid()));
    }

    public function visit(Request $req, array $p): Response
    {
        return $this->act($p, function ($lead) use ($req) {
            $err = $this->service->schedule($lead, (string) $req->input('scheduled_at', ''), (string) $req->input('note', ''), $this->uid());
            $err && $this->page->flash($err);
            return $err === null;
        });
    }

    public function destroy(Request $req, array $p): Response
    {
        if (!$this->leads->delete((int) $p['id'])) {
            return $this->notFound();
        }
        $this->page->flash('Prospect supprimé.');
        return Response::redirect('/admin/prospects');
    }

    private function act(array $p, callable $fn): Response
    {
        $lead = $this->leads->find((int) $p['id']);
        if (!$lead) {
            return $this->notFound();
        }
        $fn($lead);
        return Response::redirect('/admin/prospects/' . (int) $lead['id']);
    }

    private function uid(): ?int
    {
        $u = $this->auth->user();
        return $u ? (int) $u['id'] : null;
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Ce prospect n\'existe pas.'], 404);
    }
}
