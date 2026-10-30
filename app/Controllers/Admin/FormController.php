<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Multisite\Slug;
use App\Repositories\{FormRepository, SubmissionRepository};
use App\Services\FormDefinition;

final class FormController
{
    public function __construct(private Page $page, private FormRepository $forms, private SubmissionRepository $subs)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/forms', ['items' => $this->forms->all(), 'title' => 'Formulaires']);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->edit_form(['is_active' => 1, 'fields_text' => "Nom | text | 1\nEmail | email | 1\nMessage | textarea | 1"], null, []);
    }

    public function edit(Request $req, array $p): Response
    {
        $f = $this->forms->find((int) $p['id']);
        return $f ? $this->edit_form($f + ['fields_text' => FormDefinition::toText(json_decode($f['fields'], true) ?: [])], (int) $f['id'], []) : $this->notFound();
    }

    public function store(Request $req, array $p): Response
    {
        return $this->save($req, null);
    }

    public function update(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        return $this->forms->find($id) ? $this->save($req, $id) : $this->notFound();
    }

    public function destroy(Request $req, array $p): Response
    {
        if (!$this->forms->delete((int) $p['id'])) {
            return $this->notFound();
        }
        $this->page->flash('Formulaire supprimé.');
        return Response::redirect('/admin/formulaires');
    }

    public function submissions(Request $req, array $p): Response
    {
        $f = $this->forms->find((int) $p['id']);
        if (!$f) {
            return $this->notFound();
        }
        $rows = array_map(fn($s) => ['at' => $s['created_at'], 'data' => json_decode($s['data'], true) ?: []], $this->subs->forForm((int) $f['id']));
        return $this->page->render('admin/form_submissions', ['f' => $f, 'rows' => $rows, 'title' => 'Réponses']);
    }

    private function save(Request $req, ?int $id): Response
    {
        $in = $req->post;
        $name = trim((string) ($in['name'] ?? ''));
        $slug = Slug::make(trim((string) ($in['slug'] ?? '')) ?: $name);
        $notify = trim((string) ($in['notify_email'] ?? ''));
        [$fields, $e] = FormDefinition::parse((string) ($in['fields_text'] ?? ''));
        if ($name === '' || mb_strlen($name) > 190) {
            $e[] = 'Nom requis (190 caractères max).';
        }
        if ($notify !== '' && !filter_var($notify, FILTER_VALIDATE_EMAIL)) {
            $e[] = 'Email de notification invalide.';
        }
        if (mb_strlen((string) ($in['success_message'] ?? '')) > 300) {
            $e[] = 'Message de confirmation trop long.';
        }
        $data = ['name' => $name, 'slug' => $slug, 'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE), 'notify_email' => $notify ?: null,
            'success_message' => trim((string) ($in['success_message'] ?? '')) ?: null, 'is_active' => empty($in['is_active']) ? 0 : 1];
        if (!$e) {
            try {
                $id ? $this->forms->update($id, $data) : $this->forms->insert($data);
                $this->page->flash('Formulaire enregistré.');
                return Response::redirect('/admin/formulaires');
            } catch (\PDOException $ex) {
                if ($ex->getCode() !== '23000') {
                    throw $ex;
                }
                $e[] = 'Cette adresse est déjà utilisée par un autre formulaire.';
            }
        }
        return $this->edit_form($data + ['fields_text' => (string) ($in['fields_text'] ?? '')], $id, $e);
    }

    private function edit_form(array $f, ?int $id, array $errors): Response
    {
        return $this->page->render('admin/form_edit', ['f' => $f, 'id' => $id, 'errors' => $errors, 'types' => FormDefinition::TYPES, 'title' => 'Formulaire'], $errors ? 422 : 200);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Formulaire introuvable.'], 404);
    }
}
