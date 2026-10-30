<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Request, Response, Session, View};

final class CsrfMiddleware
{
    public function __construct(private Session $session)
    {
    }

    public function handle(Request $req, callable $next, ?string $arg = null): Response
    {
        if ($req->method === 'POST' && !$this->session->checkCsrf($req->post['_csrf'] ?? null)) {
            return Response::html(View::render('errors/message', ['title' => 'Requête refusée', 'message' => 'Jeton de sécurité invalide. Rechargez la page et réessayez.']), 419);
        }
        return $next($req);
    }
}
