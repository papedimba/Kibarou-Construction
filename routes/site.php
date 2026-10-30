<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\Admin\{ContentController, DashboardController, LandController as AdminLand, MediaController, MenuController, SettingsController};
use App\Controllers\Frontend\{HomeController, LandController, PageController, SeoController};
use App\Core\Router;
use App\Middleware\{AuthMiddleware, PermissionMiddleware};

return function (Router $r): void {
    $member = [AuthMiddleware::class . ':admin'];
    $view = [...$member, PermissionMiddleware::class . ':land.view'];
    $manage = [...$member, PermissionMiddleware::class . ':land.manage'];

    $content = [...$member, PermissionMiddleware::class . ':content.manage'];
    $settings = [...$member, PermissionMiddleware::class . ':site.settings'];

    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sitemap.xml', [SeoController::class, 'sitemap']);
    $r->get('/robots.txt', [SeoController::class, 'robots']);
    $r->get('/actualites', [PageController::class, 'posts']);
    $r->get('/actualites/{slug}', [PageController::class, 'post']);
    $r->get('/terrains', [LandController::class, 'index']);
    $r->get('/terrains/{slug}', [LandController::class, 'show']);

    $r->get('/admin/login', [AuthController::class, 'form']);
    $r->post('/admin/login', [AuthController::class, 'login']);
    $r->post('/admin/logout', [AuthController::class, 'logout']);
    $r->get('/admin', [DashboardController::class, 'index'], $member);
    $r->get('/admin/terrains', [AdminLand::class, 'index'], $view);
    $r->get('/admin/terrains/new', [AdminLand::class, 'create'], $manage);
    $r->post('/admin/terrains', [AdminLand::class, 'store'], $manage);
    $r->get('/admin/terrains/{id}/edit', [AdminLand::class, 'edit'], $manage);
    $r->post('/admin/terrains/{id}', [AdminLand::class, 'update'], $manage);
    $r->post('/admin/terrains/{id}/status', [AdminLand::class, 'status'], $manage);
    $r->post('/admin/terrains/{id}/delete', [AdminLand::class, 'destroy'], $manage);

    foreach (['pages', 'articles'] as $k) {
        $r->get("/admin/$k", [ContentController::class, 'index'], $content);
        $r->get("/admin/$k/new", [ContentController::class, 'create'], $content);
        $r->post("/admin/$k", [ContentController::class, 'store'], $content);
        $r->get("/admin/$k/{id}/edit", [ContentController::class, 'edit'], $content);
        $r->post("/admin/$k/{id}", [ContentController::class, 'update'], $content);
        $r->post("/admin/$k/{id}/delete", [ContentController::class, 'destroy'], $content);
    }
    $r->post('/admin/pages/{id}/revisions/{rev}/restore', [ContentController::class, 'restore'], $content);
    $r->get('/admin/media', [MediaController::class, 'index'], $content);
    $r->post('/admin/media', [MediaController::class, 'upload'], $content);
    $r->post('/admin/media/{id}/delete', [MediaController::class, 'destroy'], $content);
    $r->get('/admin/menus', [MenuController::class, 'index'], $content);
    $r->post('/admin/menus/items', [MenuController::class, 'addItem'], $content);
    $r->post('/admin/menus/items/{id}/delete', [MenuController::class, 'deleteItem'], $content);
    $r->post('/admin/menus/redirects', [MenuController::class, 'addRedirect'], $content);
    $r->post('/admin/menus/redirects/{id}/delete', [MenuController::class, 'deleteRedirect'], $content);
    $r->get('/admin/settings', [SettingsController::class, 'form'], $settings);
    $r->post('/admin/settings', [SettingsController::class, 'save'], $settings);

    // Must stay last: single-segment catch-all for CMS pages.
    $r->get('/{slug}', [PageController::class, 'show']);
};
