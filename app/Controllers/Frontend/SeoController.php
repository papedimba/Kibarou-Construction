<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Request, Response};
use App\Repositories\{LandRepository, PageRepository, PostRepository};

final class SeoController
{
    public function __construct(private PageRepository $pages, private PostRepository $posts, private LandRepository $lands)
    {
    }

    public function sitemap(Request $req, array $p): Response
    {
        $base = ($req->secure ? 'https' : 'http') . '://' . $req->host;
        $urls = ['/', '/terrains', '/actualites'];
        foreach ($this->pages->allPublishedSlugs() as $r) {
            $urls[] = '/' . $r['slug'];
        }
        foreach ($this->posts->allPublishedSlugs() as $r) {
            $urls[] = '/actualites/' . $r['slug'];
        }
        foreach ($this->lands->search([], true, 1, 1000)['items'] as $l) {
            $urls[] = '/terrains/' . $l['slug'];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>' . htmlspecialchars($base . $u, ENT_XML1 | ENT_QUOTES) . '</loc></url>';
        }
        // shortcut: capped at 1000 lands, split into a sitemap index beyond 50 000 URLs
        return new Response($xml . '</urlset>', 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function robots(Request $req, array $p): Response
    {
        $base = ($req->secure ? 'https' : 'http') . '://' . $req->host;
        return new Response("User-agent: *\nDisallow: /admin\nSitemap: $base/sitemap.xml\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
