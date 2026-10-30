<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\Admin\{DashboardController, LandController as AdminLand};
use App\Controllers\Frontend\{HomeController, LandController};
use App\Core\Router;
use App\Middleware\{AuthMiddleware, PermissionMiddleware};

return function (Router $r): void {
    $member = [AuthMiddleware::class . ':admin'];
    $view = [...$member, PermissionMiddleware::class . ':land.view'];
    $manage = [...$member, PermissionMiddleware::class . ':land.manage'];

    $r->get('/', [HomeController::class, 'index']);
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
};
