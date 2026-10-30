<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\RedirectRepository;

/** Unmatched path on a tenant host: 301 if a redirect exists, else 404. */
final class FallbackController
{
    public function __construct(private Page $page, private RedirectRepository $redirects)
    {
    }

    public function notFound(Request $req, array $p): Response
    {
        $to = $this->redirects->target($req->path);
        return $to ? Response::redirect($to, 301) : $this->page->render('errors/message', ['title' => 'Page introuvable', 'message' => 'Cette page n\'existe pas.'], 404);
    }
}
