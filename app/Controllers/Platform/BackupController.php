<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\{Page, Request, Response};
use App\Services\Backup;

/** Super admin only (route middleware). A backup holds every site's data: it is never reachable from a tenant host. */
final class BackupController
{
    public function __construct(private Page $page, private Backup $backup)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('platform/backups', ['items' => $this->backup->list(), 'zip' => extension_loaded('zip'), 'title' => 'Sauvegardes']);
    }

    public function create(Request $req, array $p): Response
    {
        if (!extension_loaded('zip')) {
            $this->page->flash('L\'extension PHP zip est indisponible : sauvegarde impossible.');
        } else {
            $this->backup->create();
            $this->backup->prune(30);
            $this->page->flash('Sauvegarde créée.');
        }
        return Response::redirect('/platform/backups');
    }

    public function download(Request $req, array $p): Response
    {
        $path = $this->backup->path($p['name']);
        if (!$path) {
            return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Cette sauvegarde n\'existe pas.'], 404);
        }
        // shortcut: read fully into memory; for archives over ~100 MB take them over FTP/cron instead
        return new Response((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="' . basename($path) . '"', 'Cache-Control' => 'private, no-store',
        ]);
    }
}
