<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Database, Page, Request, Response};
use App\Multisite\TenantContext;
use App\Services\AuthService;

/**
 * Members of the current site and their roles. Identity is global (one user can belong to several sites), so this
 * screen can attach an existing account to the site but never changes the password or name of an account it did
 * not create for this site alone.
 */
final class UserController
{
    public function __construct(private Page $page, private Database $db, private TenantContext $tenant, private AuthService $auth)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $members = $this->db->all(
            'SELECT m.id, m.role_id, r.slug AS role_slug, r.name AS role_name, u.id AS user_id, u.name, u.email, u.status,
                    (SELECT COUNT(DISTINCT site_id) FROM site_members x WHERE x.user_id = u.id) AS sites
             FROM site_members m JOIN users u ON u.id = m.user_id JOIN roles r ON r.id = m.role_id WHERE m.site_id = ? ORDER BY u.name, r.name',
            [$this->tenant->id()]
        );
        return $this->page->render('admin/users', ['members' => $members, 'roles' => $this->roles(), 'me' => (int) $this->auth->user()['id'], 'title' => 'Utilisateurs']);
    }

    public function add(Request $req, array $p): Response
    {
        $email = strtolower(trim((string) $req->input('email')));
        $name = trim((string) $req->input('name'));
        $role = $this->roleId((string) $req->input('role'));
        $password = (string) $req->input('password', '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$role) {
            return $this->back('Email ou rôle invalide.');
        }
        $user = $this->db->one('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user && ($name === '' || mb_strlen($password) < 12)) {
            return $this->back('Nouvel utilisateur : nom et mot de passe de 12 caractères minimum requis.');
        }
        try {
            $this->db->transaction(function (Database $db) use ($user, $email, $name, $password, $role) {
                $uid = $user['id'] ?? $db->insert('users', ['email' => $email, 'name' => mb_substr($name, 0, 190), 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
                $db->insert('site_members', ['site_id' => $this->tenant->id(), 'user_id' => $uid, 'role_id' => $role]);
            });
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            return $this->back('Cet utilisateur a déjà ce rôle sur le site.');
        }
        return $this->back($user ? 'Compte existant rattaché au site (son mot de passe n\'a pas été modifié).' : 'Utilisateur créé.');
    }

    public function role(Request $req, array $p): Response
    {
        $m = $this->member((int) $p['id']);
        $role = $this->roleId((string) $req->input('role'));
        if (!$m || !$role) {
            return $m ? $this->back('Rôle invalide.') : $this->notFound();
        }
        if ($m['role_slug'] === 'site_admin' && $role !== $this->roleId('site_admin') && $this->lastAdmin($m)) {
            return $this->back('Impossible : c\'est le dernier administrateur du site.');
        }
        try {
            $this->db->run('UPDATE site_members SET role_id = ? WHERE id = ? AND site_id = ?', [$role, $m['id'], $this->tenant->id()]);
        } catch (\PDOException $e) {
            return $this->back($e->getCode() === '23000' ? 'Cet utilisateur a déjà ce rôle.' : throw $e);
        }
        return $this->back('Rôle modifié.');
    }

    public function remove(Request $req, array $p): Response
    {
        $m = $this->member((int) $p['id']);
        if (!$m) {
            return $this->notFound();
        }
        if ($m['role_slug'] === 'site_admin' && $this->lastAdmin($m)) {
            return $this->back('Impossible : c\'est le dernier administrateur du site.');
        }
        $this->db->run('DELETE FROM site_members WHERE id = ? AND site_id = ?', [$m['id'], $this->tenant->id()]);
        return $this->back('Accès retiré (le compte n\'est pas supprimé).');
    }

    public function password(Request $req, array $p): Response
    {
        $m = $this->member((int) $p['id']);
        if (!$m) {
            return $this->notFound();
        }
        $pw = (string) $req->input('password', '');
        if ((int) $m['sites'] > 1) {
            return $this->back('Ce compte appartient à plusieurs sites : seul son titulaire peut changer son mot de passe.');
        }
        if (mb_strlen($pw) < 12) {
            return $this->back('Mot de passe de 12 caractères minimum.');
        }
        $this->db->run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $m['user_id']]);
        $this->db->run('DELETE FROM login_attempts WHERE email = ?', [$m['email']]);
        return $this->back('Mot de passe réinitialisé.');
    }

    private function roles(): array
    {
        return $this->db->all('SELECT id, slug, name FROM roles ORDER BY id');
    }

    private function roleId(string $slug): ?int
    {
        $id = $this->db->value('SELECT id FROM roles WHERE slug = ?', [$slug]);
        return $id ? (int) $id : null;
    }

    /** A membership row of THIS site only (site_id in the query: another site's id is simply "not found"). */
    private function member(int $id): ?array
    {
        return $this->db->one(
            'SELECT m.id, m.user_id, r.slug AS role_slug, u.email, (SELECT COUNT(DISTINCT site_id) FROM site_members x WHERE x.user_id = u.id) AS sites
             FROM site_members m JOIN roles r ON r.id = m.role_id JOIN users u ON u.id = m.user_id WHERE m.id = ? AND m.site_id = ?',
            [$id, $this->tenant->id()]
        );
    }

    private function lastAdmin(array $m): bool
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM site_members x JOIN roles r ON r.id = x.role_id WHERE x.site_id = ? AND r.slug = 'site_admin' AND x.id <> ?", [$this->tenant->id(), $m['id']]) === 0;
    }

    private function back(string $msg): Response
    {
        $this->page->flash($msg);
        return Response::redirect('/admin/utilisateurs');
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Cet utilisateur n\'appartient pas à ce site.'], 404);
    }
}
