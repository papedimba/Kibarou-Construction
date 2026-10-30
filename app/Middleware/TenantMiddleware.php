<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Request, Response, View};
use App\Multisite\{TenantContext, TenantResolver};

final class TenantMiddleware
{
    public function __construct(private TenantResolver $resolver, private TenantContext $ctx)
    {
    }

    public function handle(Request $req, callable $next, ?string $arg = null): Response
    {
        $site = $this->resolver->resolve($req->host);
        if (!$site || in_array($site['status'], ['pending', 'deleted'], true)) {
            return Response::html(View::render('errors/message', ['title' => 'Site introuvable', 'message' => 'Aucun site n\'est associé à ce domaine.']), 404);
        }
        if ($site['status'] === 'suspended') {
            return Response::html(View::render('errors/message', ['title' => 'Site suspendu', 'message' => 'Ce site est temporairement indisponible.']), 503);
        }
        $this->ctx->set($site);
        return $next($req);
    }
}
