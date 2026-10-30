<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Multisite\TenantContext;
use App\Repositories\{FormRepository, SubmissionRepository};
use App\Services\{AntiSpam, FormDefinition, Mailer};

/** Public side of the form builder: /f/{slug}. */
final class FormController
{
    public function __construct(private Page $page, private FormRepository $forms, private SubmissionRepository $subs, private AntiSpam $spam, private Mailer $mailer, private TenantContext $tenant)
    {
    }

    public function show(Request $req, array $p): Response
    {
        $f = $this->forms->findActiveBySlug($p['slug']);
        return $f ? $this->render($f, [], []) : $this->notFound();
    }

    public function submit(Request $req, array $p): Response
    {
        $f = $this->forms->findActiveBySlug($p['slug']);
        if (!$f) {
            return $this->notFound();
        }
        if ($err = $this->spam->check($req)) {
            return $this->render($f, $req->post, ['_form' => $err], 429);
        }
        $fields = json_decode($f['fields'], true) ?: [];
        [$data, $errors] = FormDefinition::validate($fields, $req->post);
        if ($errors) {
            return $this->render($f, $req->post, $errors, 422);
        }
        $this->subs->insert(['form_id' => $f['id'], 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'ip' => $req->ip]);
        if ($f['notify_email']) {
            $body = '';
            foreach ($data as $label => $value) {
                $body .= "$label : $value\n";
            }
            $this->mailer->send($f['notify_email'], "[{$this->tenant->site()['name']}] {$f['name']}", $body);
        }
        $this->page->flash($f['success_message'] ?: 'Merci, votre message a bien été envoyé.');
        return Response::redirect('/f/' . $f['slug']);
    }

    private function render(array $f, array $old, array $errors, int $status = 200): Response
    {
        return $this->page->render('frontend/custom_form', ['f' => $f, 'fields' => json_decode($f['fields'], true) ?: [], 'old' => $old, 'errors' => $errors, 'spam' => $this->spam->fields(), 'title' => $f['name']], $status);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Formulaire introuvable', 'message' => 'Ce formulaire n\'est pas disponible.'], 404);
    }
}
