<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Blocks\BlockRenderer;
use App\Core\{Page, Request, Response};
use App\Repositories\{PageRepository, PostRepository};

final class PageController
{
    public function __construct(private Page $page, private PageRepository $pages, private PostRepository $posts, private FallbackController $fallback, private BlockRenderer $blocks)
    {
    }

    public function show(Request $req, array $p): Response
    {
        $row = $this->pages->findPublishedBySlug($p['slug']);
        return $row ? $this->page->render('frontend/content', ['item' => $row, 'blocksHtml' => $this->blocks->renderPage($row), 'title' => $row['meta_title'] ?: $row['title'], 'heading' => $row['title'], 'metaDescription' => $row['meta_description']]) : $this->fallback->notFound($req, $p);
    }

    public function posts(Request $req, array $p): Response
    {
        return $this->page->render('frontend/posts', ['r' => $this->posts->paginate(true, (int) $req->input('page', 1), 10), 'title' => 'Actualités']);
    }

    public function post(Request $req, array $p): Response
    {
        $row = $this->posts->findPublishedBySlug($p['slug']);
        return $row ? $this->page->render('frontend/content', ['item' => $row, 'title' => $row['meta_title'] ?: $row['title'], 'heading' => $row['title'], 'metaDescription' => $row['meta_description']]) : $this->fallback->notFound($req, $p);
    }
}
