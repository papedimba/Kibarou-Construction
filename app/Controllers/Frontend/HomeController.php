<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Blocks\BlockRenderer;
use App\Multisite\TenantContext;
use App\Repositories\{DevelopmentRepository, LandImageRepository, LandRepository, PageRepository, PostRepository, ProjectImageRepository, ProjectRepository, ServiceRepository, SettingsRepository};
use App\Services\AntiSpam;

final class HomeController
{
    public function __construct(private Page $page, private LandRepository $lands, private SettingsRepository $settings, private PageRepository $pages, private BlockRenderer $blocks, private ServiceRepository $services, private ProjectRepository $projects, private TenantContext $tenant, private AntiSpam $spam,
        private LandImageRepository $landImages, private ProjectImageRepository $projectImages, private PostRepository $posts, private DevelopmentRepository $devs
    )
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
        $cfg = $this->settings->all();
        $featured = $this->lands->search(['sort' => 'recent'], true, 1, 6)['items'];
        $services = $this->services->all(true);
        $projects = array_slice($this->projects->all(true), 0, 3);
        $devs = $this->devs->all(true);
        // Real figures only, read from this site's data; a zero figure is not shown.
        $stats = array_values(array_filter([
            [(int) ($this->lands->countsByStatus()['available'] ?? 0), 'terrains disponibles', '/terrains'],
            [count($devs), 'lotissements', '/lotissements'],
            [count($services), 'services BTP', '/services'],
            [count($this->projects->all(true)), 'réalisations', '/realisations'],
        ], fn($s) => $s[0] > 0));
        return $this->page->render('frontend/home', [
            'featured' => $featured, 'covers' => $this->landImages->covers(array_map('intval', array_column($featured, 'id'))), 'cats' => $this->lands->categories(),
            'services' => array_slice($services, 0, 6), 'projects' => $projects, 'projectCovers' => $this->projectImages->covers(array_map('intval', array_column($projects, 'id'))),
            'posts' => $this->posts->paginate(true, 1, 3)['items'], 'stats' => $stats, 'spam' => $this->spam->fields(), 'types' => array_intersect_key(\App\Repositories\LeadRepository::TYPES, array_flip(['info', 'partnership', 'other'])),
            'fullBleed' => true,
            'jsonld' => array_filter([
                '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $this->tenant->site()['name'], 'url' => $base = ($req->secure ? 'https' : 'http') . '://' . $req->host,
                'logo' => !empty($cfg['logo_url']) ? $base . $cfg['logo_url'] : null, 'telephone' => $cfg['phone'] ?? null, 'email' => $cfg['email'] ?? null,
                'slogan' => $cfg['tagline'] ?? null, 'address' => !empty($cfg['address']) ? ['@type' => 'PostalAddress', 'streetAddress' => $cfg['address'], 'addressCountry' => 'CI'] : null, 'areaServed' => 'CI',
            ]),
        ]);
    }
}
