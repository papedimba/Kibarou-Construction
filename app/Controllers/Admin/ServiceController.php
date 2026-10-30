<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\ServiceRepository;
use App\Services\MediaService;

/** BTP services. New services stay inactive (hidden from the public site) until explicitly enabled. */
final class ServiceController
{
    public function __construct(private Page $page, private ServiceRepository $services, private MediaService $media)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/services', ['items' => $this->services->all(), 'cats' => ServiceRepository::CATEGORIES, 'title' => 'Services BTP']);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->form([], null, []);
    }

    public function edit(Request $req, array $p): Response
    {
        $s = $this->services->find((int) $p['id']);
        return $s ? $this->form($s, (int) $s['id'], []) : $this->notFound();
    }

    public function store(Request $req, array $p): Response
    {
        return $this->save($req, null);
    }

    public function update(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        return $this->services->find($id) ? $this->save($req, $id) : $this->notFound();
    }

    public function destroy(Request $req, array $p): Response
    {
        if (!$this->services->delete((int) $p['id'])) {
            return $this->notFound();
        }
        $this->page->flash('Service supprimé.');
        return Response::redirect('/admin/services');
    }

    private function save(Request $req, ?int $id): Response
    {
        $in = $req->post;
        $title = trim((string) ($in['title'] ?? ''));
        $cat = (string) ($in['category'] ?? '');
        $e = [];
        if ($title === '' || mb_strlen($title) > 190) {
            $e['title'] = 'Titre requis (190 caractères max).';
        }
        if (!isset(ServiceRepository::CATEGORIES[$cat])) {
            $e['category'] = 'Catégorie invalide.';
        }
        $data = ['title' => $title, 'category' => $cat, 'description' => trim((string) ($in['description'] ?? '')) ?: null, 'is_active' => empty($in['is_active']) ? 0 : 1];
        if (!$e && !empty($req->files['file']['tmp_name'])) {
            [$mediaId, $err] = $this->media->storeImage($req->files['file'], $title);
            $err ? $e['file'] = $err : $data['media_id'] = $mediaId;
        }
        if ($e) {
            return $this->form($req->post, $id, $e);
        }
        if ($id) {
            $this->services->update($id, $data);
        } else {
            $this->services->insert($data + ['slug' => $this->services->uniqueSlug($title)]);
        }
        $this->page->flash('Service enregistré.');
        return Response::redirect('/admin/services');
    }

    private function form(array $s, ?int $id, array $errors): Response
    {
        return $this->page->render('admin/service_form', ['s' => $s, 'id' => $id, 'errors' => $errors, 'cats' => ServiceRepository::CATEGORIES, 'title' => 'Service'], $errors ? 422 : 200);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Service introuvable.'], 404);
    }
}
