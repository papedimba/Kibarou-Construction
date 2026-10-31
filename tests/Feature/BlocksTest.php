<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Blocks\{BlockRegistry, BlockRenderer, BlockSanitizer, BlockStorage};
use App\Core\{Container, Response};
use App\Multisite\TenantContext;
use Tests\{DbTestCase, HttpHelpers};

final class BlocksTest extends DbTestCase
{
    use HttpHelpers;

    private int $a;
    private int $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = $this->makeSite('alpha', 'alpha.test');
        $this->b = $this->makeSite('beta', 'beta.test');
    }

    /** Sanitizer + renderer wired for one site, through the real container. */
    private function engine(int $site): array
    {
        $c = new Container();
        $c->get(TenantContext::class)->set(['id' => $site, 'name' => "Site $site"]);
        return [$c->get(BlockSanitizer::class), $c->get(BlockRenderer::class)];
    }

    private function b(string $type, array $props = [], array $children = [], string $id = ''): array
    {
        return ['id' => $id, 'type' => $type, 'props' => $props, 'children' => $children];
    }

    private function media(int $site): int
    {
        return $this->db->insert('media', ['site_id' => $site, 'filename' => bin2hex(random_bytes(6)) . '.png', 'original_name' => 'x.png', 'mime' => 'image/png', 'size' => 10, 'alt' => 'Alt']);
    }

    private function page(string $host, string $title = 'Accueil', string $slug = 'accueil'): int
    {
        $this->call('POST', '/admin/pages', $host, ['title' => $title, 'slug' => $slug, 'body' => 'Corps texte', 'status' => 'draft']);
        return (int) $this->db->value('SELECT id FROM pages WHERE slug = ? ORDER BY id DESC', [$slug]);
    }

    private function save(string $host, int $page, array $blocks, string $action = 'blocs'): Response
    {
        return $this->call('POST', "/admin/pages/$page/$action", $host, ['blocks' => $blocks]);
    }

    // — sanitizer

    public function testUnknownTypesAndUndeclaredPropsAreDropped(): void
    {
        [$san] = $this->engine($this->a);
        $out = $san->sanitize([
            $this->b('script', ['src' => 'x']), $this->b('../../etc/passwd'), ['type' => ['array']], 'string',
            $this->b('heading', ['text' => 'Salut', 'onclick' => 'alert(1)', 'html' => '<b>x</b>']),
        ]);
        $this->assertCount(1, $out);
        $this->assertSame(['text', 'level', 'align'], array_keys($out[0]['props']));
        $this->assertSame('2', $out[0]['props']['level']); // default for a missing value
    }

    public function testUrlsAreRestrictedToSafeSchemes(): void
    {
        [$san] = $this->engine($this->a);
        $ok = ['/terrains', 'https://example.com/x?y=1', 'http://example.com', 'mailto:a@b.test', 'tel:+225 07 00'];
        $bad = ['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html;base64,AAA', '//evil.com', 'vbscript:x', 'file:///etc/passwd', "/ok\nSet-Cookie: x", 'https://x y'];
        foreach ($ok as $u) {
            $this->assertSame($u, $san->sanitize([$this->b('button', ['label' => 'L', 'url' => $u])])[0]['props']['url'], $u);
        }
        foreach ($bad as $u) {
            $this->assertSame('', $san->sanitize([$this->b('button', ['label' => 'L', 'url' => $u])])[0]['props']['url'], $u);
        }
    }

    public function testValuesAreCoercedToTheirFieldType(): void
    {
        [$san] = $this->engine($this->a);
        $p = $san->sanitize([$this->b('columns', ['count' => 99, 'gap' => 'huge'])])[0]['props'];
        $this->assertSame(4, $p['count']);
        $this->assertSame('md', $p['gap']);
        $this->assertSame(2, $san->sanitize([$this->b('columns', ['count' => 'abc'])])[0]['props']['count']);
        $m = $san->sanitize([$this->b('land_map', ['lat' => '95', 'lng' => '-4.0244'])])[0]['props'];
        $this->assertSame(['', '-4.0244'], [$m['lat'], $m['lng']]);
        $t = $san->sanitize([$this->b('text', ['body' => str_repeat('é', 6000) . "\0\x07"])])[0]['props']['body'];
        $this->assertSame(5000, mb_strlen($t));
        $this->assertSame('Salut', $san->sanitize([$this->b('heading', ['text' => "  Sal\x00ut\r\n "])])[0]['props']['text']);
        $this->assertSame("a\nb", $san->sanitize([$this->b('text', ['body' => "a\r\nb"])])[0]['props']['body']);
        $this->assertSame('', $san->sanitize([$this->b('icon', ['icon' => '../../.env'])])[0]['props']['icon']);
        $this->assertSame('hammer', $san->sanitize([$this->b('icon', ['icon' => 'hammer'])])[0]['props']['icon']);
    }

    public function testImageIdsMustBelongToTheCurrentSite(): void
    {
        $mine = $this->media($this->a);
        $theirs = $this->media($this->b);
        [$san] = $this->engine($this->a);
        $out = $san->sanitize([$this->b('image', ['image' => $theirs]), $this->b('image', ['image' => $mine]), $this->b('image', ['image' => '999999']), $this->b('image', ['image' => '1 OR 1=1'])]);
        $this->assertSame([0, $mine, 0, 0], array_map(fn($b) => $b['props']['image'], $out));
    }

    public function testNestingDepthChildrenRulesAndBlockCountAreEnforced(): void
    {
        [$san] = $this->engine($this->a);
        $deep = $this->b('text', ['body' => 'deepest']);
        for ($i = 0; $i < 6; $i++) {
            $deep = $this->b('section', [], [$deep]);
        }
        $out = $san->sanitize([$deep]);
        $d = 0;
        for ($n = $out[0] ?? null; $n; $n = $n['children'][0] ?? null) {
            $d++;
        }
        $this->assertSame(BlockSanitizer::MAX_DEPTH, $d);
        $this->assertSame([], $san->sanitize([$this->b('text', ['body' => 'x'], [$this->b('text')])])[0]['children']); // leaf blocks never keep children
        $many = array_fill(0, 500, $this->b('divider'));
        $this->assertCount(BlockSanitizer::MAX_BLOCKS, $san->sanitize($many));
    }

    public function testIdsAreKeptWhenValidRegeneratedWhenNotAndAlwaysUnique(): void
    {
        [$san] = $this->engine($this->a);
        $out = $san->sanitize([$this->b('divider', [], [], 'abc123ef'), $this->b('divider', [], [], 'abc123ef'), $this->b('divider', [], [], '<x>'), $this->b('divider')]);
        $ids = array_column($out, 'id');
        $this->assertSame('abc123ef', $ids[0]);
        $this->assertCount(4, array_unique($ids));
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]{6,12}$/', $id);
        }
    }

    public function testListsAreCappedAndEmptyItemsRemoved(): void
    {
        [$san] = $this->engine($this->a);
        $items = array_fill(0, 30, ['q' => 'Q', 'a' => 'A']);
        $items[] = ['q' => '', 'a' => ''];
        $out = $san->sanitize([$this->b('faq', ['items' => $items])])[0]['props']['items'];
        $this->assertCount(20, $out);
        $this->assertCount(1, $san->sanitize([$this->b('faq', ['items' => [['q' => '', 'a' => ''], ['q' => 'Seul']]])])[0]['props']['items']);
        $this->assertSame([], $san->sanitize([$this->b('faq', ['items' => 'not-a-list'])])[0]['props']['items']);
    }

    public function testStorageIsVersionedAndNeverThrowsOnBadData(): void
    {
        $blocks = [$this->b('divider', ['style' => 'line'], [], 'abc123ef')];
        $this->assertSame(['v' => 1, 'blocks' => $blocks], json_decode(BlockStorage::encode($blocks), true));
        $this->assertSame($blocks, BlockStorage::decode(BlockStorage::encode($blocks)));
        foreach ([null, '', 'not json', '{"v":2,"blocks":[{"type":"divider"}]}', '{"blocks":[]}', '{"v":1,"blocks":"x"}', '[]'] as $bad) {
            $this->assertSame([], BlockStorage::decode($bad), (string) $bad);
        }
    }

    // — renderer

    public function testRenderingEscapesEverythingAndEmitsNoInlineStyleOrScript(): void
    {
        [$san, $r] = $this->engine($this->a);
        $evil = '"><script>alert(1)</script><img src=x onerror=alert(2)>';
        $blocks = [];
        foreach (BlockRegistry::all() as $type => $def) {
            $props = [];
            foreach ($def['fields'] as $k => $f) {
                $props[$k] = match ($f['type']) {
                    'list' => [array_map(fn($sub) => $sub['type'] === 'bool' ? true : ($sub['type'] === 'image' ? 0 : $evil), $f['item'])],
                    'text', 'textarea' => $evil,
                    default => null,
                };
            }
            $blocks[] = $this->b($type, $props);
        }
        $clean = $san->sanitize($blocks);
        foreach ([false, true] as $editor) {
            $html = $r->render($clean, $editor);
            // Structural check: parse the output, then look for anything executable. Escaped text such as
            // "&lt;img onerror=…&gt;" is just text and must not trip this.
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
            foreach ($dom->getElementsByTagName('*') as $node) {
                $this->assertNotContains($node->nodeName, ['script', 'object', 'embed', 'style'], $editor ? 'editor' : 'public');
                foreach ($node->attributes as $attr) {
                    $this->assertStringStartsNotWith('on', $attr->name, "{$node->nodeName}@{$attr->name}");
                    $this->assertNotSame('style', $attr->name);
                    $this->assertDoesNotMatchRegularExpression('/^\s*javascript:/i', $attr->value);
                }
            }
            $this->assertStringContainsString('&lt;script&gt;', $html);
        }
        $this->assertStringNotContainsString('data-block-id', $r->render($clean, false));
        $this->assertStringContainsString('data-block-id', $r->render($clean, true));
    }

    public function testTestimonialsAreOnlyPublishedWhenVerified(): void
    {
        [$san, $r] = $this->engine($this->a);
        $blocks = $san->sanitize([$this->b('testimonials', ['title' => 'Avis', 'items' => [
            ['quote' => 'Vérifié OK', 'author' => 'A', 'role' => '', 'verified' => true],
            ['quote' => 'Non vérifié', 'author' => 'B', 'role' => '', 'verified' => false],
        ]])]);
        $public = $r->render($blocks, false);
        $this->assertStringContainsString('Vérifié OK', $public);
        $this->assertStringNotContainsString('Non vérifié', $public);
        $this->assertStringContainsString('1 témoignage(s) non vérifié(s)', $r->render($blocks, true));
    }

    public function testVideoEmbedsComeFromExtractedIdsOnly(): void
    {
        [, $r] = $this->engine($this->a);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $r->video('https://www.youtube.com/watch?v=dQw4w9WgXcQ')['src']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $r->video('https://youtu.be/dQw4w9WgXcQ')['src']);
        $this->assertSame('https://player.vimeo.com/video/123456789', $r->video('https://vimeo.com/123456789')['src']);
        $this->assertSame('video', $r->video('https://cdn.example.com/a.mp4')['kind']);
        foreach (['https://evil.com/embed', 'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ', 'http://youtu.be/dQw4w9WgXcQ', 'https://x.com/a.mp4.exe', 'javascript:alert(1)'] as $bad) {
            $this->assertNull($r->video($bad), $bad);
        }
    }

    public function testDataBlocksOnlyShowTheCurrentSitesPublishedData(): void
    {
        $this->makeLand($this->a, 'MINE-1');
        $this->makeLand($this->a, 'MINE-DRAFT', ['is_published' => 0]);
        $this->makeLand($this->b, 'THEIRS-1');
        $this->db->insert('projects', ['site_id' => $this->b, 'name' => 'Projet B', 'slug' => 'projet-b', 'category' => 'autres', 'is_published' => 1]);
        $this->db->insert('construction_services', ['site_id' => $this->b, 'category' => 'autres', 'title' => 'Service B', 'slug' => 'service-b', 'is_active' => 1]);
        [$san, $r] = $this->engine($this->a);
        $html = $r->render($san->sanitize([
            $this->b('land_catalog', ['count' => 12]), $this->b('land_featured', ['reference' => 'THEIRS-1']), $this->b('land_featured', ['reference' => 'MINE-DRAFT']),
            $this->b('btp_projects', ['count' => 12]), $this->b('project_showcase', ['project' => 'projet-b']), $this->b('btp_services', ['count' => 12]), $this->b('site_gallery', ['project' => 'projet-b']),
        ]), false);
        $this->assertStringContainsString('MINE-1', $html);
        foreach (['THEIRS-1', 'MINE-DRAFT', 'Projet B', 'Service B'] as $leak) {
            $this->assertStringNotContainsString($leak, $html, $leak);
        }
    }

    // — HTTP: editor endpoints

    public function testDraftIsInvisibleUntilPublishedAndPreviewShowsTheDraft(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $res = $this->save('alpha.test', $id, [$this->b('heading', ['text' => 'Titre brouillon', 'level' => '2'])]);
        $this->assertSame(200, $res->status);
        $this->assertTrue(json_decode($res->body, true)['ok']);
        $this->assertStringNotContainsString('Titre brouillon', $this->call('GET', '/accueil', 'alpha.test')->body);
        $this->assertStringContainsString('Titre brouillon', $this->call('GET', "/admin/pages/$id/apercu", 'alpha.test')->body);
        $this->assertStringContainsString('noindex', $this->call('GET', "/admin/pages/$id/apercu", 'alpha.test')->body);

        $pub = $this->save('alpha.test', $id, [$this->b('heading', ['text' => 'Titre publié', 'level' => '2'])], 'publier');
        $this->assertTrue(json_decode($pub->body, true)['ok']);
        $page = $this->call('GET', '/accueil', 'alpha.test')->body;
        $this->assertStringContainsString('Titre publié', $page);
        $this->assertStringContainsString('<div class="blocks">', $page);
        $this->assertStringNotContainsString('data-block-id', $page);
        $this->assertSame('published', $this->db->value('SELECT status FROM pages WHERE id = ?', [$id]));

        $this->save('alpha.test', $id, [$this->b('heading', ['text' => 'Retouche non publiée', 'level' => '2'])]); // later draft edits stay private
        $this->assertStringNotContainsString('Retouche non publiée', $this->call('GET', '/accueil', 'alpha.test')->body);
        $this->assertStringContainsString('Titre publié', $this->call('GET', '/accueil', 'alpha.test')->body);
    }

    public function testPageWithoutPublishedBlocksStillShowsItsTextBody(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $this->call('POST', "/admin/pages/$id", 'alpha.test', ['title' => 'Accueil', 'slug' => 'accueil', 'body' => 'Corps texte', 'status' => 'published']);
        $this->assertStringContainsString('Corps texte', $this->call('GET', '/accueil', 'alpha.test')->body);
        $this->save('alpha.test', $id, [], 'publier'); // publishing an empty block page falls back to the text body
        $this->assertStringContainsString('Corps texte', $this->call('GET', '/accueil', 'alpha.test')->body);
    }

    public function testMalformedPayloadNeverWipesTheDraft(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $this->save('alpha.test', $id, [$this->b('heading', ['text' => 'À garder'])]);
        foreach (['blocks' => 'oops'] as $k => $v) {
            $this->assertSame(400, $this->call('POST', "/admin/pages/$id/blocs", 'alpha.test', [$k => $v])->status);
        }
        $this->assertSame(400, $this->call('POST', "/admin/pages/$id/blocs", 'alpha.test', [])->status);
        $this->assertSame(400, $this->call('POST', "/admin/pages/$id/publier", 'alpha.test', [])->status);
        $this->assertSame(400, $this->call('POST', '/admin/editeur/rendu', 'alpha.test', [])->status);
        $this->assertStringContainsString('À garder', (string) $this->db->value('SELECT draft_blocks FROM pages WHERE id = ?', [$id]));
    }

    public function testEditorEndpointsRequireCsrfAndAreTenantScoped(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $this->assertSame(419, $this->call('POST', "/admin/pages/$id/blocs", 'alpha.test', ['blocks' => []], false)->status);
        $this->login('beta.test', 'admin@beta.test');
        $this->assertSame(404, $this->call('GET', "/admin/pages/$id/editeur", 'beta.test')->status);
        $this->assertSame(404, $this->call('GET', "/admin/pages/$id/apercu", 'beta.test')->status);
        $this->assertSame(404, $this->save('beta.test', $id, [$this->b('heading', ['text' => 'Pwn'])])->status);
        $this->assertSame(404, $this->save('beta.test', $id, [$this->b('heading', ['text' => 'Pwn'])], 'publier')->status);
        $this->assertNull($this->db->value('SELECT draft_blocks FROM pages WHERE id = ?', [$id]));
        $this->assertNull($this->db->value('SELECT blocks FROM pages WHERE id = ?', [$id]));
    }

    public function testRolesAndAnonymousAccess(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $_SESSION = [];
        $this->assertSame(302, $this->call('GET', "/admin/pages/$id/editeur", 'alpha.test')->status);
        foreach (['editor' => 200, 'land_manager' => 403, 'sales_manager' => 403] as $role => $expected) {
            $uid = $this->db->insert('users', ['email' => "$role@alpha.test", 'name' => $role, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
            $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value('SELECT id FROM roles WHERE slug = ?', [$role])]);
            $_SESSION = [];
            $this->login('alpha.test', "$role@alpha.test");
            $this->assertSame($expected, $this->call('GET', "/admin/pages/$id/editeur", 'alpha.test')->status, $role);
            $this->assertSame($expected, $this->call('POST', '/admin/editeur/rendu', 'alpha.test', ['blocks' => []])->status, "$role render");
        }
    }

    public function testPublishKeepsPreviousVersionAndRestoreGoesToDraftOnly(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $this->save('alpha.test', $id, [$this->b('heading', ['text' => 'Version 1'])], 'publier');
        $this->save('alpha.test', $id, [$this->b('heading', ['text' => 'Version 2'])], 'publier');
        $revs = $this->db->all('SELECT id, blocks FROM page_revisions WHERE page_id = ? ORDER BY id', [$id]);
        $this->assertCount(2, $revs);
        $this->assertNull($revs[0]['blocks']);
        $this->assertStringContainsString('Version 1', $revs[1]['blocks']);

        $back = $this->call('POST', "/admin/pages/$id/revisions/{$revs[1]['id']}/restore", 'alpha.test', ['return' => 'editor']);
        $this->assertSame("/admin/pages/$id/editeur", $back->headers['Location']);
        $this->assertStringContainsString('Version 1', (string) $this->db->value('SELECT draft_blocks FROM pages WHERE id = ?', [$id]));
        $this->assertStringContainsString('Version 2', (string) $this->db->value('SELECT blocks FROM pages WHERE id = ?', [$id]));
        $this->assertStringContainsString('Version 2', $this->call('GET', '/accueil', 'alpha.test')->body); // visitors unaffected until re-published
    }

    public function testRenderEndpointReturnsEditorHtmlAndSanitizesInput(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $res = $this->call('POST', '/admin/editeur/rendu', 'alpha.test', ['blocks' => [$this->b('heading', ['text' => '<b>x</b>'], [], 'abc123ef'), $this->b('nope')]]);
        $html = json_decode($res->body, true)['html'];
        $this->assertStringContainsString('data-block-id="abc123ef"', $html);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('nope', $html);
    }

    public function testEditorPageEmbedsDataSafelyAndSendsStrictHeaders(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $this->save('alpha.test', $id, [$this->b('text', ['body' => '</script><script>alert(1)</script>'])]);
        $res = $this->call('GET', "/admin/pages/$id/editeur", 'alpha.test');
        $this->assertSame(200, $res->status);
        $this->assertStringNotContainsString('</script><script>alert(1)', $res->body);
        $this->assertSame(1, preg_match('#<script type="application/json" id="editor-data">(.*?)</script>#s', $res->body, $m));
        $data = json_decode($m[1], true);
        $this->assertCount(30, $data['registry']);
        $this->assertSame('</script><script>alert(1)</script>', $data['blocks'][0]['props']['body']);
        $csp = $res->headers['Content-Security-Policy'];
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString('frame-src https://www.youtube-nocookie.com', $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }

    public function testHomePageSettingUsesAPublishedBlockPageAndFallsBackSafely(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test', 'Accueil bloc', 'accueil-bloc');
        $this->save('alpha.test', $id, [$this->b('hero', ['title' => 'Hero accueil'])], 'publier');
        $this->assertStringNotContainsString('Hero accueil', $this->call('GET', '/', 'alpha.test')->body);
        $this->assertSame(422, $this->call('POST', '/admin/settings', 'alpha.test', ['theme' => 'corporate-premium', 'home_page' => '../x'])->status);
        $this->call('POST', '/admin/settings', 'alpha.test', ['theme' => 'corporate-premium', 'home_page' => 'accueil-bloc']);
        $this->assertStringContainsString('Hero accueil', $this->call('GET', '/', 'alpha.test')->body);
        $this->assertStringNotContainsString('Hero accueil', $this->call('GET', '/', 'beta.test')->body);
        $this->db->run("UPDATE pages SET status = 'draft' WHERE id = ?", [$id]);
        $this->assertStringContainsString('Construisons vos projets', $this->call('GET', '/', 'alpha.test')->body); // falls back to the default home
    }

    public function testDevPageListLinksToTheEditor(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $id = $this->page('alpha.test');
        $this->assertStringContainsString("/admin/pages/$id/editeur", $this->call('GET', '/admin/pages', 'alpha.test')->body);
    }

    public function testHomePhotoSlotUsesAPhotoOfThisSiteOnly(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $mine = $this->media($this->a);
        $theirs = $this->media($this->b);
        $file = $this->db->value('SELECT filename FROM media WHERE id = ?', [$mine]);
        $this->assertSame(422, $this->call('POST', '/admin/textes', 'alpha.test', ['img' => ['hero' => (string) $theirs]])->status);
        $this->call('POST', '/admin/textes', 'alpha.test', ['img' => ['hero' => (string) $mine]]);
        $home = $this->call('GET', '/', 'alpha.test')->body;
        $this->assertStringContainsString("/uploads/{$this->a}/$file", $home);
        $this->assertStringNotContainsString("/uploads/{$this->b}/", $home);
    }
}
