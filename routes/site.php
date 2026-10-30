<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\Admin\{ContentController, DashboardController, DevelopmentController as AdminDev, LandController as AdminLand, LandMediaController, LocationController, MediaController, MenuController, AppointmentController, FormController as AdminForm, LeadController as AdminLead, ProjectController as AdminProject, ServiceController as AdminService, SettingsController};
use App\Controllers\Frontend\{DevelopmentController, FormController, HomeController, LandController, LeadController, PageController, ProjectController, SeoController, ServiceController};
use App\Core\Router;
use App\Middleware\{AuthMiddleware, PermissionMiddleware};

return function (Router $r): void {
    $member = [AuthMiddleware::class . ':admin'];
    $view = [...$member, PermissionMiddleware::class . ':land.view'];
    $manage = [...$member, PermissionMiddleware::class . ':land.manage'];

    $btp = [...$member, PermissionMiddleware::class . ':btp.manage'];
    $leadView = [...$member, PermissionMiddleware::class . ':lead.view'];
    $leadManage = [...$member, PermissionMiddleware::class . ':lead.manage'];
    $content = [...$member, PermissionMiddleware::class . ':content.manage'];
    $settings = [...$member, PermissionMiddleware::class . ':site.settings'];

    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sitemap.xml', [SeoController::class, 'sitemap']);
    $r->get('/robots.txt', [SeoController::class, 'robots']);
    $r->get('/actualites', [PageController::class, 'posts']);
    $r->get('/actualites/{slug}', [PageController::class, 'post']);
    $r->get('/terrains', [LandController::class, 'index']);
    $r->get('/terrains/{slug}', [LandController::class, 'show']);
    $r->get('/terrains/{slug}/fiche.pdf', [LandController::class, 'pdf']);
    $r->get('/terrains/{slug}/documents/{doc}', [LandController::class, 'document']);
    $r->get('/contact', [LeadController::class, 'contact']);
    $r->post('/contact', [LeadController::class, 'submit']);
    $r->get('/demandes/merci', [LeadController::class, 'thanks']);
    $r->get('/f/{slug}', [FormController::class, 'show']);
    $r->post('/f/{slug}', [FormController::class, 'submit']);
    $r->get('/services', [ServiceController::class, 'index']);
    $r->get('/services/{slug}', [ServiceController::class, 'show']);
    $r->get('/realisations', [ProjectController::class, 'index']);
    $r->get('/realisations/{slug}', [ProjectController::class, 'show']);
    $r->get('/lotissements', [DevelopmentController::class, 'index']);
    $r->get('/lotissements/{slug}', [DevelopmentController::class, 'show']);

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

    $r->get('/admin/terrains/{id}/medias', [LandMediaController::class, 'show'], $manage);
    $r->post('/admin/terrains/{id}/images', [LandMediaController::class, 'addImage'], $manage);
    $r->post('/admin/terrains/{id}/images/{img}/delete', [LandMediaController::class, 'removeImage'], $manage);
    $r->post('/admin/terrains/{id}/documents', [LandMediaController::class, 'addDocument'], $manage);
    $r->post('/admin/terrains/{id}/documents/{doc}/verify', [LandMediaController::class, 'verifyDocument'], $manage);
    $r->post('/admin/terrains/{id}/documents/{doc}/delete', [LandMediaController::class, 'removeDocument'], $manage);
    $r->get('/admin/terrains/{id}/documents/{doc}', [LandMediaController::class, 'downloadDocument'], $manage);
    $r->get('/admin/terrains/{id}/qr.{format}', [LandMediaController::class, 'qr'], $manage);
    $r->get('/admin/terrains/{id}/fiche.pdf', [LandMediaController::class, 'pdf'], $manage);
    $r->get('/admin/localisations', [LocationController::class, 'index'], $manage);
    $r->post('/admin/localisations', [LocationController::class, 'store'], $manage);
    $r->post('/admin/localisations/{id}/delete', [LocationController::class, 'destroy'], $manage);
    $r->get('/admin/lotissements', [AdminDev::class, 'index'], $manage);
    $r->get('/admin/lotissements/new', [AdminDev::class, 'create'], $manage);
    $r->post('/admin/lotissements', [AdminDev::class, 'store'], $manage);
    $r->get('/admin/lotissements/{id}/edit', [AdminDev::class, 'edit'], $manage);
    $r->post('/admin/lotissements/{id}', [AdminDev::class, 'update'], $manage);
    $r->post('/admin/lotissements/{id}/delete', [AdminDev::class, 'destroy'], $manage);
    $r->post('/admin/lotissements/{id}/parcelles', [AdminDev::class, 'addPlot'], $manage);
    $r->post('/admin/lotissements/{id}/parcelles/{plot}', [AdminDev::class, 'updatePlot'], $manage);
    $r->post('/admin/lotissements/{id}/parcelles/{plot}/delete', [AdminDev::class, 'deletePlot'], $manage);
    foreach (['services' => AdminService::class, 'realisations' => AdminProject::class] as $k => $ctl) {
        $r->get("/admin/$k", [$ctl, 'index'], $btp);
        $r->get("/admin/$k/new", [$ctl, 'create'], $btp);
        $r->post("/admin/$k", [$ctl, 'store'], $btp);
        $r->get("/admin/$k/{id}/edit", [$ctl, 'edit'], $btp);
        $r->post("/admin/$k/{id}", [$ctl, 'update'], $btp);
        $r->post("/admin/$k/{id}/delete", [$ctl, 'destroy'], $btp);
    }
    $r->post('/admin/realisations/{id}/images', [AdminProject::class, 'addImage'], $btp);
    $r->post('/admin/realisations/{id}/images/{img}/delete', [AdminProject::class, 'removeImage'], $btp);

    $r->get('/admin/prospects', [AdminLead::class, 'index'], $leadView);
    $r->get('/admin/prospects/export.csv', [AdminLead::class, 'export'], $leadView);
    $r->get('/admin/prospects/{id}', [AdminLead::class, 'show'], $leadView);
    $r->post('/admin/prospects/{id}/status', [AdminLead::class, 'status'], $leadManage);
    $r->post('/admin/prospects/{id}/assign', [AdminLead::class, 'assign'], $leadManage);
    $r->post('/admin/prospects/{id}/notes', [AdminLead::class, 'note'], $leadManage);
    $r->post('/admin/prospects/{id}/visites', [AdminLead::class, 'visit'], $leadManage);
    $r->post('/admin/prospects/{id}/delete', [AdminLead::class, 'destroy'], $leadManage);
    $r->get('/admin/visites', [AppointmentController::class, 'index'], $leadView);
    $r->post('/admin/visites/{id}', [AppointmentController::class, 'update'], $leadManage);
    $r->get('/admin/formulaires', [AdminForm::class, 'index'], $content);
    $r->get('/admin/formulaires/new', [AdminForm::class, 'create'], $content);
    $r->post('/admin/formulaires', [AdminForm::class, 'store'], $content);
    $r->get('/admin/formulaires/{id}/edit', [AdminForm::class, 'edit'], $content);
    $r->post('/admin/formulaires/{id}', [AdminForm::class, 'update'], $content);
    $r->post('/admin/formulaires/{id}/delete', [AdminForm::class, 'destroy'], $content);
    $r->get('/admin/formulaires/{id}/reponses', [AdminForm::class, 'submissions'], $content);

    // Must stay last: single-segment catch-all for CMS pages.
    $r->get('/{slug}', [PageController::class, 'show']);
};
