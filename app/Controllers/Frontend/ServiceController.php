<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\{MediaRepository, ServiceRepository};

final class ServiceController
{
    public function __construct(private Page $page, private ServiceRepository $services, private MediaRepository $media)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $items = $this->services->all(true);
        return $this->page->render('frontend/services', ['items' => $items, 'cats' => ServiceRepository::CATEGORIES, 'images' => $this->images($items), 'title' => 'Nos services BTP']);
    }

    public function show(Request $req, array $p): Response
    {
        $s = $this->services->findBySlug($p['slug']);
        return $s
            ? $this->page->render('frontend/service', ['s' => $s, 'cats' => ServiceRepository::CATEGORIES, 'images' => $this->images([$s]), 'title' => $s['title']])
            : $this->page->render('errors/message', ['title' => 'Service introuvable', 'message' => 'Ce service n\'est pas disponible.'], 404);
    }

    /** @return array<int,array> media id => row */
    private function images(array $items): array
    {
        $out = [];
        foreach ($items as $i) {
            if ($i['media_id'] && ($m = $this->media->find((int) $i['media_id']))) {
                $out[(int) $i['media_id']] = $m;
            }
        }
        return $out;
    }
}
