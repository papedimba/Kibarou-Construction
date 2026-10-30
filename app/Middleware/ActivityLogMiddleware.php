<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Database, Request, Response};
use App\Multisite\TenantContext;
use App\Services\AuthService;

/**
 * Audit trail: one row per successful state-changing request made by a signed-in user (who, where, which action, from which IP).
 * No request body is stored (it may hold passwords or personal data). Failed (>= 400) and anonymous requests are not logged here.
 */
final class ActivityLogMiddleware
{
    public function __construct(private Database $db, private AuthService $auth, private TenantContext $tenant)
    {
    }

    public function handle(Request $req, callable $next, ?string $arg = null): Response
    {
        $res = $next($req);
        if ($req->method === 'POST' && $res->status < 400 && ($user = $this->auth->user())) {
            $this->db->insert('activity_logs', [
                'site_id' => $this->tenant->has() ? $this->tenant->id() : null, 'user_id' => $user['id'],
                'action' => mb_substr('POST ' . $req->path, 0, 100), 'ip' => mb_substr($req->ip, 0, 45),
            ]);
        }
        return $res;
    }
}
