<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\{Database, Page, Request, Response};
use App\Multisite\{DomainManager, TenantProvisioner};

final class SiteController
{
    public function __construct(private Page $page, private Database $db, private TenantProvisioner $prov, private DomainManager $domains)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $sites = $this->db->all('SELECT s.*, (SELECT GROUP_CONCAT(host SEPARATOR ", ") FROM domains d WHERE d.site_id = s.id) AS hosts FROM sites s ORDER BY s.id');
        return $this->page->render('platform/sites', ['sites' => $sites]);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->page->render('platform/new_site', ['error' => null, 'old' => []]);
    }

    public function store(Request $req, array $p): Response
    {
        try {
            $this->prov->create(
                trim((string) $req->input('name')), (string) $req->input('slug'), (string) $req->input('host'),
                (string) $req->input('admin_email'), trim((string) $req->input('admin_name')), (string) $req->input('password')
            );
        } catch (\InvalidArgumentException | \DomainException $e) {
            return $this->page->render('platform/new_site', ['error' => $e->getMessage(), 'old' => $req->post], 422);
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            return $this->page->render('platform/new_site', ['error' => 'Identifiant ou domaine déjà utilisé.', 'old' => $req->post], 422);
        }
        $this->page->flash('Site créé.');
        return Response::redirect('/platform/sites');
    }

    public function status(Request $req, array $p): Response
    {
        $status = (string) $req->input('status');
        if (in_array($status, ['active', 'suspended'], true)) {
            $this->db->run('UPDATE sites SET status = ? WHERE id = ?', [$status, (int) $p['id']]);
            $this->page->flash('Statut mis à jour.');
        }
        return Response::redirect('/platform/sites');
    }

    public function addDomain(Request $req, array $p): Response
    {
        try {
            $this->domains->attach((int) $p['id'], (string) $req->input('host'));
            $this->page->flash('Domaine ajouté.');
        } catch (\InvalidArgumentException | \DomainException $e) {
            $this->page->flash($e->getMessage());
        }
        return Response::redirect('/platform/sites');
    }
}
