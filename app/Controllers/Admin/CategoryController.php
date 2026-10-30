<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Database, Page, Request, Response};
use App\Multisite\{Slug, TenantContext};
use App\Repositories\LandRepository;

/** Land categories of the current site (the nine defaults are only a starting point). */
final class CategoryController
{
    public function __construct(private Page $page, private Database $db, private TenantContext $tenant, private LandRepository $lands)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $rows = $this->db->all('SELECT c.*, (SELECT COUNT(*) FROM land_properties l WHERE l.site_id = c.site_id AND l.category_id = c.id) AS uses FROM land_categories c WHERE c.site_id = ? ORDER BY c.name', [$this->tenant->id()]);
        return $this->page->render('admin/categories', ['rows' => $rows, 'title' => 'Catégories de terrains']);
    }

    public function store(Request $req, array $p): Response
    {
        $name = trim((string) $req->input('name'));
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->back('Nom requis (100 caractères max).');
        }
        try {
            $this->db->insert('land_categories', ['site_id' => $this->tenant->id(), 'name' => $name, 'slug' => Slug::make($name)]);
        } catch (\PDOException $e) {
            return $this->back($e->getCode() === '23000' ? 'Une catégorie de ce nom existe déjà.' : throw $e);
        }
        return $this->back('Catégorie ajoutée.');
    }

    public function destroy(Request $req, array $p): Response
    {
        try {
            $n = $this->db->run('DELETE FROM land_categories WHERE id = ? AND site_id = ?', [(int) $p['id'], $this->tenant->id()])->rowCount();
        } catch (\PDOException $e) {
            return $this->back($e->getCode() === '23000' ? 'Impossible : des terrains utilisent cette catégorie.' : throw $e);
        }
        return $n ? $this->back('Catégorie supprimée.') : $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Catégorie introuvable.'], 404);
    }

    private function back(string $m): Response
    {
        $this->page->flash($m);
        return Response::redirect('/admin/categories');
    }
}
