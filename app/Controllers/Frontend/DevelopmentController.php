<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\{DevelopmentRepository, LocationRepository, PlotRepository};

final class DevelopmentController
{
    public function __construct(private Page $page, private DevelopmentRepository $devs, private PlotRepository $plots, private LocationRepository $locations)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('frontend/developments', ['items' => $this->devs->all(true), 'title' => 'Nos lotissements']);
    }

    public function show(Request $req, array $p): Response
    {
        $d = $this->devs->findBySlug($p['slug']);
        return $d
            ? $this->page->render('frontend/development', ['d' => $d, 'plots' => $this->plots->forDevelopment((int) $d['id']), 'location' => $this->locations->label($d['location_id'] ? (int) $d['location_id'] : null), 'title' => $d['name']])
            : $this->page->render('errors/message', ['title' => 'Lotissement introuvable', 'message' => 'Ce lotissement n\'est pas disponible.'], 404);
    }
}
