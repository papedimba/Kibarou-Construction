<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Blocks\BlockRenderer;
use App\Repositories\{LandRepository, PageRepository, SettingsRepository};

final class HomeController
{
    public function __construct(private Page $page, private LandRepository $lands, private SettingsRepository $settings, private PageRepository $pages, private BlockRenderer $blocks)
    {
    }

    public function index(Request $req, array $p): Response
    {
        // A published block page chosen as home page (setting home_page) replaces the default home.
        $slug = $this->settings->all()['home_page'] ?? '';
        $row = $slug !== '' ? $this->pages->findPublishedBySlug($slug) : null;
        if ($row && ($html = $this->blocks->renderPage($row))) {
            return $this->page->render('frontend/content', ['item' => $row, 'blocksHtml' => $html, 'title' => $row['meta_title'] ?: $row['title'], 'metaDescription' => $row['meta_description']]);
        }
        return $this->page->render('frontend/home', ['featured' => $this->lands->search(['sort' => 'recent'], true, 1, 3)['items']]);
    }
}
