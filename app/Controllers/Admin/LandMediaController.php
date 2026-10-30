<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{LandDocumentRepository, LandImageRepository, LandRepository};
use App\Services\{DocumentService, LandSheet, MediaService, QrService};

/** Photos, private documents, QR code and PDF sheet of one land. Every action starts by loading the land through the tenant repository. */
final class LandMediaController
{
    public function __construct(
        private Page $page, private LandRepository $lands, private LandImageRepository $images, private LandDocumentRepository $docs,
        private MediaService $media, private DocumentService $docService, private QrService $qr, private LandSheet $sheet
    ) {
    }

    public function show(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        return $land ? $this->page->render('admin/land_media', [
            'land' => $land, 'images' => $this->images->forOwner((int) $land['id']), 'documents' => $this->docs->forLand((int) $land['id']),
            'docTypes' => LandDocumentRepository::TYPES, 'title' => 'Photos et documents',
        ]) : $this->notFound();
    }

    public function addImage(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        if (!$land) {
            return $this->notFound();
        }
        [$mediaId, $err] = $this->media->storeImage($req->files['file'] ?? [], (string) $req->input('alt', ''));
        if ($mediaId) {
            $this->images->attach((int) $land['id'], $mediaId);
        }
        $this->page->flash($err ?? 'Photo ajoutée.');
        return Response::redirect("/admin/terrains/{$land['id']}/medias");
    }

    public function removeImage(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        $img = $land ? $this->images->find((int) $p['img']) : null;
        if (!$img || (int) $img['land_id'] !== (int) $land['id']) {
            return $this->notFound();
        }
        $this->images->delete((int) $img['id']);
        $this->media->delete((int) $img['media_id']);
        return Response::redirect("/admin/terrains/{$land['id']}/medias");
    }

    public function addDocument(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        if (!$land) {
            return $this->notFound();
        }
        $err = $this->docService->store($req->files['file'] ?? [], (int) $land['id'], (string) $req->input('type'), (string) $req->input('doc_date', ''), (string) $req->input('visibility', 'private'));
        $this->page->flash($err ?? 'Document ajouté (non vérifié).');
        return Response::redirect("/admin/terrains/{$land['id']}/medias");
    }

    public function verifyDocument(Request $req, array $p): Response
    {
        $doc = $this->ownDoc($p);
        if (!$doc) {
            return $this->notFound();
        }
        $this->docs->update((int) $doc['id'], ['verification' => $req->input('verification') === 'verified' ? 'verified' : 'unverified']);
        return Response::redirect("/admin/terrains/{$doc['land_id']}/medias");
    }

    public function removeDocument(Request $req, array $p): Response
    {
        $doc = $this->ownDoc($p);
        if (!$doc) {
            return $this->notFound();
        }
        $this->docService->delete((int) $doc['id']);
        return Response::redirect("/admin/terrains/{$doc['land_id']}/medias");
    }

    public function downloadDocument(Request $req, array $p): Response
    {
        $doc = $this->ownDoc($p);
        return $doc ? $this->docService->download($doc) : $this->notFound();
    }

    public function qr(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        if (!$land) {
            return $this->notFound();
        }
        $format = ($p['format'] ?? 'svg') === 'png' ? 'png' : 'svg';
        [$bytes, $mime] = $this->qr->render(($req->secure ? 'https' : 'http') . '://' . $req->host . '/terrains/' . $land['slug'], $format);
        return new Response($bytes, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'attachment; filename="qr-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $land['reference']) . ".$format\""]);
    }

    public function pdf(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        return $land ? $this->sheet->response($land, $req) : $this->notFound();
    }

    private function ownDoc(array $p): ?array
    {
        $land = $this->lands->find((int) $p['id']);
        $doc = $land ? $this->docs->find((int) $p['doc']) : null;
        return $doc && (int) $doc['land_id'] === (int) $land['id'] ? $doc : null;
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Élément introuvable.'], 404);
    }
}
