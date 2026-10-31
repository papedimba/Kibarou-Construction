<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Request;
use App\Multisite\TenantContext;
use App\Repositories\MediaRepository;
use App\Services\MediaService;
use Tests\{DbTestCase, HttpHelpers};

final class CmsTest extends DbTestCase
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

    private function savePage(string $host, array $over = []): \App\Core\Response
    {
        return $this->call('POST', '/admin/pages', $host, $over + ['title' => 'À propos', 'body' => "Bonjour\n\nDeuxième paragraphe", 'status' => 'published']);
    }

    private function png(): array
    {
        $f = tempnam(sys_get_temp_dir(), 'png');
        $im = imagecreatetruecolor(4, 4);
        imagepng($im, $f);
        return ['name' => 'photo.png', 'tmp_name' => $f, 'error' => UPLOAD_ERR_OK];
    }

    public function testPublishedPageIsServedOnlyOnItsOwnSite(): void
    {
        $this->assertSame(302, $this->savePage('alpha.test')->status);
        $this->assertStringContainsString('<p>Bonjour</p>', $this->call('GET', '/a-propos', 'alpha.test')->body);
        // /a-propos is a built-in page: on the other site it shows that site's own template, never alpha's page
        $this->assertStringNotContainsString('Bonjour', $this->call('GET', '/a-propos', 'beta.test')->body);
    }

    public function testSameSlugAllowedOnTwoSitesAndDraftIsHidden(): void
    {
        $this->savePage('alpha.test');
        $this->login('beta.test', 'admin@beta.test');
        $this->assertSame(302, $this->savePage('beta.test', ['status' => 'draft'])->status);
        $this->assertStringNotContainsString('Deuxième paragraphe', $this->call('GET', '/a-propos', 'beta.test')->body); // draft not served
        $this->assertSame(422, $this->savePage('beta.test', ['title' => 'Dup'] + ['slug' => 'a-propos'])->status);
    }

    public function testReservedSlugIsRejected(): void
    {
        $this->assertSame(422, $this->savePage('alpha.test', ['title' => 'x', 'slug' => 'terrains'])->status);
        $this->assertSame(422, $this->savePage('alpha.test', ['title' => 'x', 'slug' => 'admin'])->status);
    }

    public function testBodyIsEscapedNotExecuted(): void
    {
        $this->savePage('alpha.test', ['title' => 'XSS', 'body' => '<script>alert(1)</script>']);
        $body = $this->call('GET', '/xss', 'alpha.test')->body;
        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function testOtherTenantsPageCannotBeEditedOrDeleted(): void
    {
        $this->login('beta.test', 'admin@beta.test');
        $this->savePage('beta.test', ['title' => 'Secret']);
        $id = (int) $this->db->value("SELECT id FROM pages WHERE slug = 'secret'");
        $this->login('alpha.test', 'admin@alpha.test');
        $this->assertSame(404, $this->call('GET', "/admin/pages/$id/edit", 'alpha.test')->status);
        $this->assertSame(404, $this->call('POST', "/admin/pages/$id/delete", 'alpha.test')->status);
        $this->assertSame(404, $this->call('POST', "/admin/pages/$id", 'alpha.test', ['title' => 'Pwn', 'status' => 'published'])->status);
        $this->assertSame('Secret', $this->db->value('SELECT title FROM pages WHERE id = ?', [$id]));
    }

    public function testRevisionIsKeptAndRestorable(): void
    {
        $this->savePage('alpha.test', ['body' => 'V1']);
        $id = (int) $this->db->value("SELECT id FROM pages WHERE slug = 'a-propos'");
        $this->call('POST', "/admin/pages/$id", 'alpha.test', ['title' => 'À propos', 'body' => 'V2', 'status' => 'published']);
        $rev = (int) $this->db->value('SELECT id FROM page_revisions WHERE page_id = ?', [$id]);
        $this->assertSame('V2', $this->db->value('SELECT body FROM pages WHERE id = ?', [$id]));
        $this->call('POST', "/admin/pages/$id/revisions/$rev/restore", 'alpha.test');
        $this->assertSame('V1', $this->db->value('SELECT body FROM pages WHERE id = ?', [$id]));
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM page_revisions WHERE page_id = ?', [$id]));
    }

    public function testRevisionOfAnotherPageCannotBeRestoredOntoMine(): void
    {
        $this->savePage('alpha.test', ['title' => 'P1', 'body' => 'one']);
        $this->savePage('alpha.test', ['title' => 'P2', 'body' => 'two']);
        [$p1, $p2] = array_map('intval', array_column($this->db->all('SELECT id FROM pages ORDER BY id'), 'id'));
        $this->call('POST', "/admin/pages/$p2", 'alpha.test', ['title' => 'P2', 'body' => 'two-b', 'status' => 'published']);
        $revOfP2 = (int) $this->db->value('SELECT id FROM page_revisions WHERE page_id = ?', [$p2]);
        $this->assertSame(404, $this->call('POST', "/admin/pages/$p1/revisions/$revOfP2/restore", 'alpha.test')->status);
    }

    public function testPostsListAndSitemapOnlyContainOwnPublishedContent(): void
    {
        $this->call('POST', '/admin/articles', 'alpha.test', ['title' => 'Nouveau chantier', 'body' => 'x', 'status' => 'published']);
        $this->call('POST', '/admin/articles', 'alpha.test', ['title' => 'Brouillon', 'body' => 'x', 'status' => 'draft']);
        $this->savePage('alpha.test');
        $this->makeLand($this->a, 'A1');
        $this->makeLand($this->b, 'B1');
        $this->assertStringContainsString('Nouveau chantier', $this->call('GET', '/actualites', 'alpha.test')->body);
        $this->assertStringNotContainsString('Brouillon', $this->call('GET', '/actualites', 'alpha.test')->body);
        $xml = $this->call('GET', '/sitemap.xml', 'alpha.test')->body;
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('http://alpha.test/a-propos', $xml);
        $this->assertStringContainsString('/actualites/nouveau-chantier', $xml);
        $this->assertStringContainsString('/terrains/a1', $xml);
        $this->assertStringNotContainsString('brouillon', $xml);
        $this->assertStringNotContainsString('/terrains/b1', $xml);
        $this->assertStringContainsString('Sitemap: http://alpha.test/sitemap.xml', $this->call('GET', '/robots.txt', 'alpha.test')->body);
    }

    public function testSeoMetaAndCanonicalAreRendered(): void
    {
        $this->savePage('alpha.test', ['meta_title' => 'Titre SEO', 'meta_description' => 'Desc "quotée"']);
        $body = $this->call('GET', '/a-propos', 'alpha.test')->body;
        $this->assertStringContainsString('<title>Titre SEO — Site alpha</title>', $body);
        $this->assertStringContainsString('content="Desc &quot;quotée&quot;"', $body);
        $this->assertStringContainsString('<link rel="canonical" href="http://alpha.test/a-propos">', $body);
    }

    public function testMenuRejectsJavascriptUrlAndIsPerTenant(): void
    {
        $this->call('POST', '/admin/menus/items', 'alpha.test', ['location' => 'header', 'label' => 'Bad', 'url' => 'javascript:alert(1)']);
        $this->call('POST', '/admin/menus/items', 'alpha.test', ['location' => 'header', 'label' => 'Contact', 'url' => '/nous-joindre']);
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertStringContainsString('href="/nous-joindre"', $home);
        $this->assertStringNotContainsString('javascript:', $home);
        $this->assertStringNotContainsString('href="/nous-joindre"', $this->call('GET', '/', 'beta.test')->body);
    }

    public function testRedirectIs301OnlyOnItsSite(): void
    {
        $this->call('POST', '/admin/menus/redirects', 'alpha.test', ['from_path' => '/ancien', 'to_url' => '/terrains']);
        $res = $this->call('GET', '/ancien', 'alpha.test');
        $this->assertSame(301, $res->status);
        $this->assertSame('/terrains', $res->headers['Location']);
        $this->assertSame(404, $this->call('GET', '/ancien', 'beta.test')->status);
        $this->call('POST', '/admin/menus/redirects', 'alpha.test', ['from_path' => '/x', 'to_url' => 'javascript:alert(1)']);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM redirects'));
    }

    public function testSettingsAreValidatedAndAppliedPerTenant(): void
    {
        $bad = $this->call('POST', '/admin/settings', 'alpha.test', ['theme' => '../../etc', 'email' => 'nope', 'logo_url' => 'https://evil/x.png']);
        $this->assertSame(422, $bad->status);
        $this->call('POST', '/admin/settings', 'alpha.test', ['theme' => 'elegance-prestige', 'phone' => '+225 01 02', 'whatsapp' => '', 'tagline' => '<b>Slogan</b>']);
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertStringContainsString('themes/elegance-prestige.css', $home);
        $this->assertStringContainsString('&lt;b&gt;Slogan', $home);
        $this->assertStringNotContainsString('wa.me', $home);
        $this->assertStringContainsString('themes/corporate-premium.css', $this->call('GET', '/', 'beta.test')->body);
        $this->call('POST', '/admin/settings', 'alpha.test', ['whatsapp' => '+225 07 00 00 00 00']);
        $this->assertStringContainsString('https://wa.me/2250700000000', $this->call('GET', '/', 'alpha.test')->body);
    }

    public function testMediaRejectsDisguisedScriptAndAcceptsRealImage(): void
    {
        $ctx = new TenantContext();
        $ctx->set(['id' => $this->a]);
        $svc = new MediaService(new MediaRepository($this->db, $ctx), $ctx);
        $evil = tempnam(sys_get_temp_dir(), 'evil');
        file_put_contents($evil, '<?php system($_GET["c"]); ?>');
        $this->assertNotNull($svc->store(['name' => 'shell.php.jpg', 'tmp_name' => $evil, 'error' => UPLOAD_ERR_OK], '', false));
        $svg = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->assertNotNull($svc->store(['name' => 'x.png', 'tmp_name' => $svg, 'error' => UPLOAD_ERR_OK], '', false));
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM media'));

        $this->assertNull($svc->store($this->png(), 'Vue du chantier', false));
        $m = $this->db->one('SELECT * FROM media');
        $this->assertSame($this->a, (int) $m['site_id']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{24}\.png$/', $m['filename']);
        $this->assertFileExists($svc->dir() . '/' . $m['filename']);
        $this->assertTrue($svc->delete((int) $m['id']));
        $this->assertFileDoesNotExist($svc->dir() . '/' . $m['filename']);
    }

    public function testMediaOfOtherTenantCannotBeDeleted(): void
    {
        $ctxB = new TenantContext();
        $ctxB->set(['id' => $this->b]);
        $svcB = new MediaService(new MediaRepository($this->db, $ctxB), $ctxB);
        $svcB->store($this->png(), '', false);
        $id = (int) $this->db->value('SELECT id FROM media');
        $this->assertSame(404, $this->call('POST', "/admin/media/$id/delete", 'alpha.test')->status);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM media'));
        $svcB->delete($id);
    }

    public function testEditorRoleCanManageContentButNotSettings(): void
    {
        $uid = $this->db->insert('users', ['email' => 'ed@alpha.test', 'name' => 'Ed', 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value("SELECT id FROM roles WHERE slug='editor'")]);
        $this->login('alpha.test', 'ed@alpha.test');
        $this->assertSame(200, $this->call('GET', '/admin/pages', 'alpha.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/settings', 'alpha.test')->status);
        $this->assertSame(403, $this->call('POST', '/admin/settings', 'alpha.test', ['theme' => 'elegance-prestige'])->status);
    }
}
