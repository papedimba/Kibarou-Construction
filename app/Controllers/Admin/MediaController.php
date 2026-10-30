<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\MediaRepository;
use App\Services\MediaService;

final class MediaController
{
    public function __construct(private Page $page, private MediaRepository $media, private MediaService $service)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/media', ['items' => $this->media->all(), 'title' => 'Médias']);
    }

    public function upload(Request $req, array $p): Response
    {
        $err = $this->service->store($req->files['file'] ?? [], (string) $req->input('alt', ''));
        $this->page->flash($err ?? 'Image ajoutée.');
        return Response::redirect('/admin/media');
    }

    public function destroy(Request $req, array $p): Response
    {
        if (!$this->service->delete((int) $p['id'])) {
            return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Ce média n\'existe pas.'], 404);
        }
        $this->page->flash('Média supprimé.');
        return Response::redirect('/admin/media');
    }
}
