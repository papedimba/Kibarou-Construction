<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Page, Request, Response};
use App\Multisite\TenantContext;
use App\Services\{AuthService, Rbac};

/** Shared login for /platform (super admins) and /admin (site members). */
final class AuthController
{
    public function __construct(private Page $page, private AuthService $auth, private Rbac $rbac, private TenantContext $tenant)
    {
    }

    public function form(Request $req, array $p): Response
    {
        return $this->page->render('auth/login', ['area' => $this->area($req), 'error' => null]);
    }

    public function login(Request $req, array $p): Response
    {
        $area = $this->area($req);
        $err = $this->auth->attempt((string) $req->input('email', ''), (string) $req->input('password', ''), $req->ip);
        if ($err === null) {
            $user = $this->auth->user();
            $allowed = $area === 'platform' ? (bool) $user['is_super_admin'] : $this->rbac->isMember($user, $this->tenant->id());
            if ($allowed) {
                return Response::redirect($area === 'platform' ? '/platform/sites' : '/admin');
            }
            $this->auth->logout();
            $err = 'Identifiants incorrects.'; // same message: do not reveal that the account exists elsewhere
        }
        return $this->page->render('auth/login', ['area' => $area, 'error' => $err], 401);
    }

    public function logout(Request $req, array $p): Response
    {
        $this->auth->logout();
        return Response::redirect($this->area($req) === 'platform' ? '/platform/login' : '/admin/login');
    }

    private function area(Request $req): string
    {
        return str_starts_with($req->path, '/platform') ? 'platform' : 'admin';
    }
}
