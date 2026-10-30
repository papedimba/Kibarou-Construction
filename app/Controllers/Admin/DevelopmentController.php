<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{DevelopmentRepository, LandRepository, LocationRepository, PlotRepository};

/** Land developments (lotissements) and their plots (parcelles). */
final class DevelopmentController
{
    public function __construct(private Page $page, private DevelopmentRepository $devs, private PlotRepository $plots, private LocationRepository $locations)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/developments', ['items' => $this->devs->all(), 'title' => 'Lotissements']);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->form([], null, []);
    }

    public function edit(Request $req, array $p): Response
    {
        $d = $this->devs->find((int) $p['id']);
        return $d ? $this->form($d, (int) $d['id'], []) : $this->notFound();
    }

    public function store(Request $req, array $p): Response
    {
        [$data, $err] = $this->collect($req);
        if ($err) {
            return $this->form($req->post, null, $err);
        }
        $data['slug'] = $this->devs->uniqueSlug($data['name']);
        $id = $this->devs->insert($data);
        $this->page->flash('Lotissement créé. Ajoutez maintenant ses parcelles.');
        return Response::redirect("/admin/lotissements/$id/edit");
    }

    public function update(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        if (!$this->devs->find($id)) {
            return $this->notFound();
        }
        [$data, $err] = $this->collect($req);
        if ($err) {
            return $this->form($req->post, $id, $err);
        }
        $this->devs->update($id, $data);
        $this->page->flash('Lotissement enregistré.');
        return Response::redirect("/admin/lotissements/$id/edit");
    }

    public function destroy(Request $req, array $p): Response
    {
        if (!$this->devs->delete((int) $p['id'])) {
            return $this->notFound();
        }
        $this->page->flash('Lotissement supprimé (parcelles incluses).');
        return Response::redirect('/admin/lotissements');
    }

    public function addPlot(Request $req, array $p): Response
    {
        $id = (int) $p['id'];
        if (!$this->devs->find($id)) {
            return $this->notFound();
        }
        $in = $req->post;
        $ref = trim((string) ($in['reference'] ?? ''));
        $num = trim((string) ($in['number'] ?? ''));
        $status = $in['status'] ?? '';
        if (!preg_match('/^[A-Za-z0-9_\/-]{1,40}$/', $ref) || !preg_match('/^[A-Za-z0-9_\/-]{1,20}$/', $num)
            || !ctype_digit((string) ($in['area_m2'] ?? '')) || (int) $in['area_m2'] < 1 || !ctype_digit((string) ($in['price_xof'] ?? ''))
            || !in_array($status, LandRepository::STATUSES, true)) {
            $this->page->flash('Parcelle invalide (référence, numéro, superficie, prix, statut).');
        } else {
            try {
                $this->plots->insert(['development_id' => $id, 'reference' => $ref, 'number' => $num, 'area_m2' => (int) $in['area_m2'], 'price_xof' => (int) $in['price_xof'], 'status' => $status, 'location_note' => mb_substr(trim((string) ($in['location_note'] ?? '')), 0, 190) ?: null]);
                $this->page->flash('Parcelle ajoutée.');
            } catch (\PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $this->page->flash('Référence ou numéro déjà utilisé.');
            }
        }
        return Response::redirect("/admin/lotissements/$id/edit");
    }

    public function updatePlot(Request $req, array $p): Response
    {
        $plot = $this->plots->find((int) $p['plot']);
        if (!$plot || (int) $plot['development_id'] !== (int) $p['id']) {
            return $this->notFound();
        }
        $status = (string) $req->input('status');
        if (in_array($status, LandRepository::STATUSES, true)) {
            $this->plots->update((int) $plot['id'], ['status' => $status]);
        }
        return Response::redirect("/admin/lotissements/{$plot['development_id']}/edit");
    }

    public function deletePlot(Request $req, array $p): Response
    {
        $plot = $this->plots->find((int) $p['plot']);
        if (!$plot || (int) $plot['development_id'] !== (int) $p['id']) {
            return $this->notFound();
        }
        $this->plots->delete((int) $plot['id']);
        return Response::redirect("/admin/lotissements/{$plot['development_id']}/edit");
    }

    private function form(array $dev, ?int $id, array $errors): Response
    {
        return $this->page->render('admin/development_form', [
            'dev' => $dev, 'id' => $id, 'errors' => $errors, 'locs' => $this->locations->tree(), 'plots' => $id ? $this->plots->forDevelopment($id) : [],
            'statuses' => LandRepository::STATUSES, 'title' => 'Lotissement',
        ], $errors ? 422 : 200);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Élément introuvable.'], 404);
    }

    /** @return array{0:array,1:array} */
    private function collect(Request $req): array
    {
        $in = $req->post;
        $name = trim((string) ($in['name'] ?? ''));
        $loc = ($in['location_id'] ?? '') === '' ? null : (int) $in['location_id'];
        $e = [];
        if ($name === '' || mb_strlen($name) > 190) {
            $e['name'] = 'Nom requis (190 caractères max).';
        }
        if ($loc !== null && !in_array($loc, array_map('intval', array_column($this->locations->tree(), 'id')), true)) {
            $e['location_id'] = 'Localisation invalide.';
        }
        return [['name' => $name, 'location_id' => $loc, 'description' => trim((string) ($in['description'] ?? '')) ?: null, 'is_published' => empty($in['is_published']) ? 0 : 1], $e];
    }
}
