<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Request, Response, View};
use App\Multisite\TenantContext;
use App\Services\{AuthService, Rbac};

/** Arg: "platform" (super admins only) or "admin" (members of the current tenant). */
final class AuthMiddleware
{
    public function __construct(private AuthService $auth, private Rbac $rbac, private TenantContext $tenant)
    {
    }

    public function handle(Request $req, callable $next, ?string $area = 'admin'): Response
    {
        $user = $this->auth->user();
        if (!$user) {
            return Response::redirect("/$area/login");
        }
        $allowed = $area === 'platform' ? (bool) $user['is_super_admin'] : $this->rbac->isMember($user, $this->tenant->id());
        return $allowed ? $next($req) : self::forbidden();
    }

    public static function forbidden(): Response
    {
        return Response::html(View::render('errors/message', ['title' => 'Accès refusé', 'message' => 'Vous n\'avez pas les droits nécessaires.']), 403);
    }
}
