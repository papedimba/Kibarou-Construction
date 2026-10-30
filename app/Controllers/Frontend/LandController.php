<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\{LandDocumentRepository, LandImageRepository, LandRepository, LocationRepository};
use App\Services\{DocumentService, LandSheet};

final class LandController
{
    public function __construct(
        private Page $page, private LandRepository $lands, private LandImageRepository $images, private LandDocumentRepository $docs,
        private LocationRepository $locations, private DocumentService $docService, private LandSheet $sheet
    ) {
    }

    public function index(Request $req, array $p): Response
    {
        $r = $this->lands->search($req->query, true, (int) $req->input('page', 1), 12);
        return $this->page->render('frontend/lands', [
            'r' => $r, 'f' => $req->query, 'cats' => $this->lands->categories(), 'statuses' => LandRepository::STATUSES,
            'locs' => $this->locations->tree(), 'covers' => $this->images->covers(array_map('intval', array_column($r['items'], 'id'))), 'title' => 'Nos terrains',
        ]);
    }

    public function show(Request $req, array $p): Response
    {
        $land = $this->lands->findBySlug($p['slug']);
        if (!$land) {
            return $this->notFound();
        }
        $similar = $this->lands->similar($land);
        $hasGps = $land['latitude'] !== null && $land['longitude'] !== null;
        return $this->page->render('frontend/land', [
            'land' => $land, 'title' => $land['title'], 'images' => $this->images->forOwner((int) $land['id']),
            'documents' => $this->docs->forLand((int) $land['id'], true), 'docTypes' => LandDocumentRepository::TYPES,
            'location' => $this->locations->label($land['location_id'] ? (int) $land['location_id'] : null),
            'similar' => $similar, 'covers' => $this->images->covers(array_map('intval', array_column($similar, 'id'))),
            'mapUrl' => $hasGps ? sprintf('https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=16/%s/%s', $land['latitude'], $land['longitude'], $land['latitude'], $land['longitude']) : null,
        ]);
    }

    public function pdf(Request $req, array $p): Response
    {
        $land = $this->lands->findBySlug($p['slug']);
        return $land ? $this->sheet->response($land, $req) : $this->notFound();
    }

    /** Only documents flagged public, of a published land, ever leave storage/private through this route. */
    public function document(Request $req, array $p): Response
    {
        $land = $this->lands->findBySlug($p['slug']);
        $doc = $land ? $this->docs->find((int) $p['doc']) : null;
        if (!$doc || (int) $doc['land_id'] !== (int) $land['id'] || $doc['visibility'] !== 'public') {
            return $this->notFound();
        }
        return $this->docService->download($doc);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Terrain introuvable', 'message' => 'Ce terrain n\'est pas disponible.'], 404);
    }
}
