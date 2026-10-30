<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\{ProjectImageRepository, ProjectRepository, ServiceRepository};

final class ProjectController
{
    public function __construct(private Page $page, private ProjectRepository $projects, private ProjectImageRepository $images)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $items = $this->projects->all(true);
        return $this->page->render('frontend/projects', ['items' => $items, 'covers' => $this->images->covers(array_map('intval', array_column($items, 'id'))), 'title' => 'Nos réalisations']);
    }

    public function show(Request $req, array $p): Response
    {
        $r = $this->projects->findBySlug($p['slug']);
        return $r
            ? $this->page->render('frontend/project', ['r' => $r, 'images' => $this->images->forOwner((int) $r['id']), 'cats' => ServiceRepository::CATEGORIES, 'statuses' => ProjectRepository::STATUSES, 'title' => $r['name']])
            : $this->page->render('errors/message', ['title' => 'Réalisation introuvable', 'message' => 'Cette réalisation n\'est pas disponible.'], 404);
    }
}
