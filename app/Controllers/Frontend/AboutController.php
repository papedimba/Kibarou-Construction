<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\PageRepository;

/** /a-propos: a published CMS page with that address wins (the owner's own content); otherwise the editable template. */
final class AboutController
{
    public function __construct(private Page $page, private PageRepository $pages, private PageController $cms)
    {
    }

    public function show(Request $req, array $p): Response
    {
        if ($this->pages->findPublishedBySlug('a-propos')) {
            return $this->cms->show($req, ['slug' => 'a-propos']);
        }
        return $this->page->render('frontend/about', ['title' => 'À propos', 'heading' => 'À propos de ' . ($this->page->siteName() ?? '')]);
    }
}
