<?php
declare(strict_types=1);

use App\Controllers\{AccountController, AuthController};
use App\Controllers\Platform\{BackupController, SiteController};
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return function (Router $r): void {
    $auth = [AuthMiddleware::class . ':platform'];
    $r->get('/platform/login', [AuthController::class, 'form']);
    $r->post('/platform/login', [AuthController::class, 'login']);
    $r->post('/platform/logout', [AuthController::class, 'logout']);
    $r->get('/platform/compte', [AccountController::class, 'form'], $auth);
    $r->post('/platform/compte', [AccountController::class, 'save'], $auth);
    $r->get('/platform/sites', [SiteController::class, 'index'], $auth);
    $r->get('/platform/sites/new', [SiteController::class, 'create'], $auth);
    $r->post('/platform/sites', [SiteController::class, 'store'], $auth);
    $r->post('/platform/sites/{id}/status', [SiteController::class, 'status'], $auth);
    $r->post('/platform/sites/{id}/delete', [SiteController::class, 'delete'], $auth);
    $r->get('/platform/backups', [BackupController::class, 'index'], $auth);
    $r->post('/platform/backups', [BackupController::class, 'create'], $auth);
    $r->get('/platform/backups/{name}', [BackupController::class, 'download'], $auth);
    $r->post('/platform/sites/{id}/domains', [SiteController::class, 'addDomain'], $auth);
};
