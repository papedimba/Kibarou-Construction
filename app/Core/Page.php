<?php
declare(strict_types=1);

namespace App\Core;

use App\Multisite\TenantContext;
use App\Services\AuthService;

/** Renders a view with the common layout data (csrf token, flash, current site/user). */
final class Page
{
    public function __construct(private Session $session, private TenantContext $tenant, private AuthService $auth)
    {
    }

    public function render(string $tpl, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($tpl, $data + [
            'csrf' => $this->session->csrf(),
            'flash' => $this->session->flash(),
            'site' => $this->tenant->has() ? $this->tenant->site() : null,
            'user' => $this->auth->user(),
        ]), $status);
    }

    public function flash(string $m): void
    {
        $this->session->flash($m);
    }
}
