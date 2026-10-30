<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Request, Response};
use App\Multisite\TenantContext;
use App\Services\{AuthService, Rbac};

/** Arg: permission slug, checked for the current user on the current tenant. */
final class PermissionMiddleware
{
    public function __construct(private AuthService $auth, private Rbac $rbac, private TenantContext $tenant)
    {
    }

    public function handle(Request $req, callable $next, ?string $perm = null): Response
    {
        $user = $this->auth->user();
        return $user && $perm && $this->rbac->can($user, $this->tenant->id(), $perm) ? $next($req) : AuthMiddleware::forbidden();
    }
}
