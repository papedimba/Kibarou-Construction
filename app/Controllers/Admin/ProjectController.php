<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{ProjectImageRepository, ProjectRepository, ServiceRepository};
use App\Services\MediaService;

/** Portfolio of projects (réalisations). Nothing is pre-filled: only real projects entered by the company. */
final class ProjectController
{
    public function __construct(private Page $page, private ProjectRepository $projects, private ProjectImageRepository $images, private MediaService $media)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/projects', ['items' => $this->projects->all(), 'statuses' => ProjectRepository::STATUSES, 'title' => 'Réalisations']);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->form(['status' => 'completed'], null, []);
    }

    public function edit(Request $req, array $p): Response
    {
        $r = $this->projects->find((int) $p['id']);
        return $r ? $this->form($r, (int) $r['id'], []) : $this->notFound();
    }

    public function store(Request $req, array $p): Response
    {
        [$data, $e] = $this->collect($req);
        if ($e) {
            return $this->form($req->post, null, $e);
        }
        $id = $this->projects->insert($data + ['slug' => $this->projects->uniqueSlug($data['name'])]);
        $this->page->flash('Réalisation créée. Vous pouvez ajouter des photos.');
        return Response::redirect("/admin/realisations/$id/edit");
    }

    public function update(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        if (!$this->projects->find($id)) {
            return $this->notFound();
        }
        [$data, $e] = $this->collect($req);
        if ($e) {
            return $this->form($req->post, $id, $e);
        }
        $this->projects->update($id, $data);
        $this->page->flash('Réalisation enregistrée.');
        return Response::redirect("/admin/realisations/$id/edit");
    }

    public function destroy(Request $req, array $p): Response
    {
        if (!$this->projects->delete((int) $p['id'])) {
            return $this->notFound();
        }
        $this->page->flash('Réalisation supprimée.');
        return Response::redirect('/admin/realisations');
    }

    public function addImage(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        if (!$this->projects->find($id)) {
            return $this->notFound();
        }
        [$mediaId, $err] = $this->media->storeImage($req->files['file'] ?? [], (string) $req->input('alt', ''));
        if ($mediaId) {
            $this->images->attach($id, $mediaId);
        }
        $this->page->flash($err ?? 'Photo ajoutée.');
        return Response::redirect("/admin/realisations/$id/edit");
    }

    public function removeImage(Request $req, array $p): Response
    {
        $img = $this->images->find((int) $p['img']);
        if (!$img || (int) $img['project_id'] !== (int) $p['id']) {
            return $this->notFound();
        }
        $this->images->delete((int) $img['id']);
        $this->media->delete((int) $img['media_id']);
        return Response::redirect("/admin/realisations/{$img['project_id']}/edit");
    }

    private function form(array $r, ?int $id, array $errors): Response
    {
        return $this->page->render('admin/project_form', [
            'r' => $r, 'id' => $id, 'errors' => $errors, 'cats' => ServiceRepository::CATEGORIES, 'statuses' => ProjectRepository::STATUSES,
            'images' => $id ? $this->images->forOwner($id) : [], 'title' => 'Réalisation',
        ], $errors ? 422 : 200);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Réalisation introuvable.'], 404);
    }

    /** @return array{0:array,1:array} */
    private function collect(Request $req): array
    {
        $in = $req->post;
        $name = trim((string) ($in['name'] ?? ''));
        $date = trim((string) ($in['completed_on'] ?? ''));
        $e = [];
        if ($name === '' || mb_strlen($name) > 190) {
            $e['name'] = 'Nom requis (190 caractères max).';
        }
        if (!isset(ServiceRepository::CATEGORIES[$in['category'] ?? ''])) {
            $e['category'] = 'Catégorie invalide.';
        }
        if (!isset(ProjectRepository::STATUSES[$in['status'] ?? ''])) {
            $e['status'] = 'Statut invalide.';
        }
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $e['completed_on'] = 'Date invalide.';
        }
        return [[
            'name' => $name, 'category' => $in['category'] ?? '', 'status' => $in['status'] ?? '', 'completed_on' => $date ?: null,
            'location_text' => mb_substr(trim((string) ($in['location_text'] ?? '')), 0, 190) ?: null,
            'description' => trim((string) ($in['description'] ?? '')) ?: null, 'is_published' => empty($in['is_published']) ? 0 : 1,
        ], $e];
    }
}
