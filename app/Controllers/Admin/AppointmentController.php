<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\AppointmentRepository;

final class AppointmentController
{
    public function __construct(private Page $page, private AppointmentRepository $appointments)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/appointments', ['items' => $this->appointments->upcoming(), 'statuses' => AppointmentRepository::STATUSES, 'title' => 'Visites']);
    }

    public function update(Request $req, array $p): Response
    {
        $a = $this->appointments->find((int) $p['id']);
        if (!$a) {
            return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Visite introuvable.'], 404);
        }
        $s = (string) $req->input('status');
        if (isset(AppointmentRepository::STATUSES[$s])) {
            $this->appointments->update((int) $a['id'], ['status' => $s]);
        }
        return Response::redirect('/admin/visites');
    }
}
