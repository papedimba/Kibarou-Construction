<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\LocationRepository;

/** Geographic hierarchy (district > région > … > quartier), editable without touching code. */
final class LocationController
{
    public function __construct(private Page $page, private LocationRepository $locations)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('admin/locations', ['tree' => $this->locations->tree(), 'types' => LocationRepository::TYPES, 'title' => 'Localisations']);
    }

    public function store(Request $req, array $p): Response
    {
        $type = (string) $req->input('type');
        $name = trim((string) $req->input('name'));
        $parent = ($req->input('parent_id', '') === '') ? null : (int) $req->input('parent_id');
        $valid = array_map('intval', array_column($this->locations->tree(), 'id'));
        if (!isset(LocationRepository::TYPES[$type]) || $name === '' || mb_strlen($name) > 150 || ($parent !== null && !in_array($parent, $valid, true))) {
            $this->page->flash('Localisation invalide.');
        } else {
            $this->locations->insert(['type' => $type, 'name' => $name, 'parent_id' => $parent]);
            $this->page->flash('Localisation ajoutée.');
        }
        return Response::redirect('/admin/localisations');
    }

    public function destroy(Request $req, array $p): Response
    {
        try {
            $this->locations->delete((int) $p['id']);
            $this->page->flash('Localisation supprimée.');
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $this->page->flash('Impossible : cette localisation est utilisée (terrain, lotissement ou sous-localisation).');
        }
        return Response::redirect('/admin/localisations');
    }
}
