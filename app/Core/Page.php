<?php
declare(strict_types=1);

namespace App\Core;

use App\Multisite\TenantContext;
use App\Repositories\{MenuRepository, SettingsRepository};
use App\Services\AuthService;

/** Renders a view with the common layout data (csrf token, flash, current site/user). */
final class Page
{
    public function __construct(private Session $session, private TenantContext $tenant, private AuthService $auth, private Request $req, private SettingsRepository $settings, private MenuRepository $menus)
    {
    }

    public function render(string $tpl, array $data = [], int $status = 200): Response
    {
        $has = $this->tenant->has();
        $public = $has && !str_starts_with($tpl, 'admin/') && !str_starts_with($tpl, 'auth/');
        return Response::html(View::render($tpl, $data + [
            'settings' => $has ? $this->settings->all() : [],
            'headerMenu' => $public ? $this->menus->items('header') : [],
            'footerMenu' => $public ? $this->menus->items('footer') : [],
            'canonical' => $this->req->url(),
            'adminNav' => $has && str_starts_with($tpl, 'admin/'),
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
