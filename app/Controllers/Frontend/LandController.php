<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\LandRepository;

final class LandController
{
    public function __construct(private Page $page, private LandRepository $lands)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $r = $this->lands->search($req->query, true, (int) $req->input('page', 1), 12);
        return $this->page->render('frontend/lands', ['r' => $r, 'f' => $req->query, 'cats' => $this->lands->categories(), 'statuses' => LandRepository::STATUSES]);
    }

    public function show(Request $req, array $p): Response
    {
        $land = $this->lands->findBySlug($p['slug']);
        return $land
            ? $this->page->render('frontend/land', ['land' => $land])
            : $this->page->render('errors/message', ['title' => 'Terrain introuvable', 'message' => 'Ce terrain n\'est pas disponible.'], 404);
    }
}
