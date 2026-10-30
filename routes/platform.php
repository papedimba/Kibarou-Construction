<?php
declare(strict_types=1);

use App\Controllers\{AuthController};
use App\Controllers\Platform\SiteController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return function (Router $r): void {
    $auth = [AuthMiddleware::class . ':platform'];
    $r->get('/platform/login', [AuthController::class, 'form']);
    $r->post('/platform/login', [AuthController::class, 'login']);
    $r->post('/platform/logout', [AuthController::class, 'logout']);
    $r->get('/platform/sites', [SiteController::class, 'index'], $auth);
    $r->get('/platform/sites/new', [SiteController::class, 'create'], $auth);
    $r->post('/platform/sites', [SiteController::class, 'store'], $auth);
    $r->post('/platform/sites/{id}/status', [SiteController::class, 'status'], $auth);
    $r->post('/platform/sites/{id}/domains', [SiteController::class, 'addDomain'], $auth);
};
