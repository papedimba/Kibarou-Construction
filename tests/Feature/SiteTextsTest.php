<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\{DbTestCase, HttpHelpers};

final class SiteTextsTest extends DbTestCase
{
    use HttpHelpers;

    private int $a;
    private int $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = $this->makeSite('alpha', 'alpha.test');
        $this->b = $this->makeSite('beta', 'beta.test');
        $this->login('alpha.test', 'admin@alpha.test');
    }

    public function testProvisionalTextsUseTheSiteNameAndAreFlaggedInTheAdmin(): void
    {
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertStringContainsString('Site alpha vous accompagne', $home);   // {site} placeholder, never another company's name
        $this->assertStringNotContainsString('KIBAROU', $home);
        $admin = $this->call('GET', '/admin/textes', 'alpha.test')->body;
        $this->assertStringContainsString('Provisoire, à remplacer', $admin);
        $this->assertStringContainsString('Cahier des charges', $admin);
        $this->assertStringContainsString('Vide, section masquée', $admin);
    }

    public function testEditingIsPerSiteAndEmptyingRestoresTheDefault(): void
    {
        $this->call('POST', '/admin/textes', 'alpha.test', ['txt' => ['home_intro_title' => 'Notre vraie présentation <b>x</b>']]);
        $this->assertStringContainsString('Notre vraie présentation &lt;b&gt;x&lt;/b&gt;', $this->call('GET', '/', 'alpha.test')->body);
        $this->assertStringNotContainsString('Notre vraie présentation', $this->call('GET', '/', 'beta.test')->body);
        $this->assertStringContainsString('Personnalisé', $this->call('GET', '/admin/textes', 'alpha.test')->body);
        $this->call('POST', '/admin/textes', 'alpha.test', ['txt' => ['home_intro_title' => '']]);
        $this->assertStringContainsString('Du terrain à la remise des clés', $this->call('GET', '/', 'alpha.test')->body);
    }

    public function testInvalidInputIsRejectedAndUnknownKeysIgnored(): void
    {
        $this->assertSame(422, $this->call('POST', '/admin/textes', 'alpha.test', ['txt' => ['home_cta_title' => str_repeat('x', 500)]])->status);
        $this->call('POST', '/admin/textes', 'alpha.test', ['txt' => ['logo_url' => '/evil', 'nope' => 'x']]);
        $this->assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM settings WHERE `key` IN ('txt.logo_url','txt.nope','logo_url')"));
    }

    public function testEmptySectionsAreHiddenRatherThanInvented(): void
    {
        $about = $this->call('GET', '/a-propos', 'alpha.test')->body;
        $this->assertStringContainsString('Notre mission', $about);
        $this->assertStringNotContainsString('Notre histoire', $about); // empty by default: hidden
        $this->assertStringNotContainsString('Notre équipe', $about);
        $this->call('POST', '/admin/textes', 'alpha.test', ['txt' => ['about_history' => 'Fondée par …']]);
        $this->assertStringContainsString('Notre histoire', $this->call('GET', '/a-propos', 'alpha.test')->body);
    }

    public function testHomeFiguresComeFromRealDataOnly(): void
    {
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertStringNotContainsString('class="figures', $home); // no data → no figures at all
        $this->makeLand($this->a, 'A1');
        $this->makeLand($this->a, 'A2');
        $this->makeLand($this->a, 'SOLD', ['status' => 'sold']);
        $this->makeLand($this->b, 'B1');
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertMatchesRegularExpression('#<strong>2</strong><span>terrains disponibles</span>#', $home);
        $this->assertStringNotContainsString('réalisations</span>', $home); // zero → not shown
    }

    public function testAboutPageUsesAPublishedCmsPageWhenOneExists(): void
    {
        $this->call('POST', '/admin/pages', 'alpha.test', ['title' => 'À propos', 'slug' => 'a-propos', 'body' => 'Texte du propriétaire', 'status' => 'published']);
        $this->assertStringContainsString('Texte du propriétaire', $this->call('GET', '/a-propos', 'alpha.test')->body);
        $this->assertStringContainsString('Notre mission', $this->call('GET', '/a-propos', 'beta.test')->body);
    }

    public function testInnerPagesHaveOneH1InTheBannerAndTheHomeHasNoBanner(): void
    {
        $this->makeLand($this->a, 'A1');
        foreach (['/terrains', '/terrains/a1', '/services', '/a-propos', '/contact', '/actualites'] as $path) {
            $body = $this->call('GET', $path, 'alpha.test')->body;
            $this->assertSame(1, substr_count($body, '<h1'), "h1 count on $path");
            $this->assertStringContainsString('class="banner"', $body, $path);
        }
        $this->assertStringContainsString('Accueil</a> <span aria-hidden="true">›</span> <a href="/terrains">Nos terrains', $this->call('GET', '/terrains/a1', 'alpha.test')->body);
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertStringNotContainsString('class="banner"', $home);
        $this->assertSame(1, substr_count($home, '<h1'));
    }

    public function testTextsScreenNeedsContentPermission(): void
    {
        $uid = $this->db->insert('users', ['email' => 'land@alpha.test', 'name' => 'L', 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value("SELECT id FROM roles WHERE slug = 'land_manager'")]);
        $_SESSION = [];
        $this->login('alpha.test', 'land@alpha.test');
        $this->assertSame(403, $this->call('GET', '/admin/textes', 'alpha.test')->status);
        $this->assertSame(403, $this->call('POST', '/admin/textes', 'alpha.test', ['txt' => ['home_intro_title' => 'x']])->status);
    }
}
