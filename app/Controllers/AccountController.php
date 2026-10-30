<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Page, Request, Response};
use App\Services\AuthService;

/** "My account": change the signed-in user's own password. /admin/compte (site members) and /platform/compte (super admins). */
final class AccountController
{
    public function __construct(private Page $page, private AuthService $auth)
    {
    }

    public function form(Request $req, array $p): Response
    {
        return $this->page->render('admin/account', ['title' => 'Mon compte', 'error' => null]);
    }

    public function save(Request $req, array $p): Response
    {
        $user = $this->auth->user();
        $err = $this->auth->changePassword($user, (string) $req->input('current', ''), (string) $req->input('new', ''), (string) $req->input('confirm', ''), $req->ip);
        if ($err === null) {
            $this->page->flash('Mot de passe modifié.');
            return Response::redirect($req->path);
        }
        return $this->page->render('admin/account', ['title' => 'Mon compte', 'error' => $err], 422);
    }
}
