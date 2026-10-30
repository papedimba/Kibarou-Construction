<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{LandRepository, LocationRepository};
use App\Services\DocumentService;

final class LandController
{
    public function __construct(private Page $page, private LandRepository $lands, private LocationRepository $locations, private DocumentService $docs)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $r = $this->lands->search($req->query, false, (int) $req->input('page', 1), 20);
        return $this->page->render('admin/lands', ['r' => $r, 'f' => $req->query, 'cats' => $this->lands->categories(), 'statuses' => LandRepository::STATUSES, 'locs' => $this->locations->tree()]);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->form(['status' => 'available'], [], null);
    }

    public function edit(Request $req, array $p): Response
    {
        $land = $this->lands->find((int) $p['id']);
        return $land ? $this->form($land, [], (int) $land['id']) : $this->notFound();
    }

    public function store(Request $req, array $p): Response
    {
        [$data, $errors] = $this->collect($req, null);
        if ($errors) {
            return $this->form($req->post, $errors, null);
        }
        $data['slug'] = $this->lands->uniqueSlug($data['title']);
        $this->lands->insert($data);
        $this->page->flash('Terrain créé.');
        return Response::redirect('/admin/terrains');
    }

    public function update(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        if (!$this->lands->find($id)) {
            return $this->notFound();
        }
        [$data, $errors] = $this->collect($req, $id);
        if ($errors) {
            return $this->form($req->post, $errors, $id);
        }
        $this->lands->update($id, $data);
        $this->page->flash('Terrain modifié.');
        return Response::redirect('/admin/terrains');
    }

    public function status(Request $req, array $p): Response
    {
        $s = (string) $req->input('status');
        if (!in_array($s, LandRepository::STATUSES, true) || !$this->lands->find((int) $p['id'])) {
            return $this->notFound();
        }
        $this->lands->update((int) $p['id'], ['status' => $s]);
        $this->page->flash('Statut mis à jour.');
        return Response::redirect('/admin/terrains');
    }

    public function destroy(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        $files = $this->docs->pathsForLand($id); // the DB cascade removes the rows, so list the files first
        try {
            if (!$this->lands->delete($id)) {
                return $this->notFound();
            }
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $this->page->flash('Suppression impossible : des demandes de prospects sont liées à ce terrain. Passez-le en « Indisponible ».');
            return Response::redirect('/admin/terrains');
        }
        array_map('unlink', array_filter($files, 'is_file'));
        $this->page->flash('Terrain supprimé.');
        return Response::redirect('/admin/terrains');
    }

    private function form(array $land, array $errors, ?int $id): Response
    {
        return $this->page->render('admin/land_form', ['land' => $land, 'errors' => $errors, 'id' => $id, 'cats' => $this->lands->categories(), 'statuses' => LandRepository::STATUSES, 'locs' => $this->locations->tree()], $errors ? 422 : 200);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Ce terrain n\'existe pas.'], 404);
    }

    /** @return array{0:array,1:array<string,string>} */
    private function collect(Request $req, ?int $id): array
    {
        $in = $req->post;
        $e = [];
        $title = trim((string) ($in['title'] ?? ''));
        $ref = trim((string) ($in['reference'] ?? ''));
        $area = $in['area_m2'] ?? '';
        $price = $in['price_xof'] ?? '';
        $status = $in['status'] ?? '';
        $title === '' || mb_strlen($title) > 190 and $e['title'] = 'Désignation requise (190 caractères max).';
        preg_match('/^[A-Za-z0-9_\/-]{1,40}$/', $ref) or $e['reference'] = 'Référence requise (lettres, chiffres, - _ /).';
        ctype_digit((string) $area) && (int) $area > 0 or $e['area_m2'] = 'Superficie invalide.';
        ctype_digit((string) $price) or $e['price_xof'] = 'Prix invalide (FCFA, entier).';
        in_array($status, LandRepository::STATUSES, true) or $e['status'] = 'Statut invalide.';
        $catId = ($in['category_id'] ?? '') === '' ? null : (int) $in['category_id'];
        if ($catId !== null && !in_array($catId, array_map('intval', array_column($this->lands->categories(), 'id')), true)) {
            $e['category_id'] = 'Catégorie invalide.';
        }
        $locId = ($in['location_id'] ?? '') === '' ? null : (int) $in['location_id'];
        if ($locId !== null && !in_array($locId, array_map('intval', array_column($this->locations->tree(), 'id')), true)) {
            $e['location_id'] = 'Localisation invalide.';
        }
        $lat = trim((string) ($in['latitude'] ?? ''));
        $lng = trim((string) ($in['longitude'] ?? ''));
        if (($lat !== '' || $lng !== '') && (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180)) {
            $e['latitude'] = 'Coordonnées GPS invalides (latitude -90..90, longitude -180..180).';
        }
        if (!isset($e['reference'])) {
            $dup = $this->lands->search(['q' => $ref], false, 1, 50)['items'];
            foreach ($dup as $d) {
                if ($d['reference'] === $ref && (int) $d['id'] !== $id) {
                    $e['reference'] = 'Cette référence existe déjà.';
                }
            }
        }
        return [[
            'title' => $title, 'reference' => $ref, 'category_id' => $catId, 'description' => trim((string) ($in['description'] ?? '')) ?: null,
            'location_id' => $locId, 'latitude' => $lat === '' ? null : $lat, 'longitude' => $lng === '' ? null : $lng,
            'features' => trim((string) ($in['features'] ?? '')) ?: null, 'land_info' => trim((string) ($in['land_info'] ?? '')) ?: null,
            'area_m2' => (int) $area, 'price_xof' => (int) $price, 'status' => $status, 'is_published' => empty($in['is_published']) ? 0 : 1,
        ], $e];
    }
}
