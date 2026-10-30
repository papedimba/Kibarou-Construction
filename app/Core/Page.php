<?php
declare(strict_types=1);

namespace App\Core;

use App\Multisite\TenantContext;
use App\Repositories\{MenuRepository, PageRepository, SettingsRepository};
use App\Services\{AuthService, Rbac};

/** Renders a view with the common layout data (csrf token, flash, current site/user). */
final class Page
{
    public function __construct(private Session $session, private TenantContext $tenant, private AuthService $auth, private Request $req, private SettingsRepository $settings, private MenuRepository $menus, private PageRepository $pages, private Rbac $rbac)
    {
    }

    public function render(string $tpl, array $data = [], int $status = 200): Response
    {
        $has = $this->tenant->has();
        $user = $this->auth->user();
        $public = $has && !str_starts_with($tpl, 'admin/') && !str_starts_with($tpl, 'auth/');
        return Response::html(View::render($tpl, $data + [
            'settings' => $has ? $this->settings->all() : [],
            'headerMenu' => $public ? $this->menus->items('header') : [],
            'footerMenu' => $public ? $this->menus->items('footer') : [],
            'perms' => $user && $has ? $this->rbac->permissions($user, $this->tenant->id()) : ($user ? ['*'] : []),
            'currentPath' => $this->req->path,
            'authScreen' => str_starts_with($tpl, 'auth/'),
            'navDefault' => $public ? $this->defaultNav() : [],
            'canonical' => $this->req->url(),
            'adminNav' => $has && str_starts_with($tpl, 'admin/'),
            'csrf' => $this->session->csrf(),
            'flash' => $this->session->flash(),
            'site' => $this->tenant->has() ? $this->tenant->site() : null,
            'user' => $user,
        ]), $status);
    }

    public function flash(string $m): void
    {
        $this->session->flash($m);
    }

    /** Navigation shown while the site has no custom header menu: the sections the site really has. */
    private function defaultNav(): array
    {
        $nav = [['label' => 'Accueil', 'url' => '/']];
        if ($this->pages->findPublishedBySlug('a-propos')) {
            $nav[] = ['label' => 'À propos', 'url' => '/a-propos'];
        }
        return [...$nav, ['label' => 'Nos terrains', 'url' => '/terrains'], ['label' => 'Nos services BTP', 'url' => '/services'], ['label' => 'Nos réalisations', 'url' => '/realisations'], ['label' => 'Actualités', 'url' => '/actualites'], ['label' => 'Contact', 'url' => '/contact']];
    }
}
