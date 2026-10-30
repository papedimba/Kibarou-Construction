<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Multisite\TenantProvisioner;
use Tests\{DbTestCase, HttpHelpers};

final class OpsTest extends DbTestCase
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

    private function jsonld(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m), 'no JSON-LD block');
        $d = json_decode($m[1], true);
        $this->assertIsArray($d, 'JSON-LD is not valid JSON');
        return $d;
    }

    private function superAdmin(): void
    {
        $this->db->insert('users', ['email' => 'root@cms.test', 'name' => 'Root', 'is_super_admin' => 1, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $_SESSION = [];
        $this->login('platform.test', 'root@cms.test', area: 'platform');
    }

    public function testHomeCarriesOrganizationStructuredData(): void
    {
        $this->db->insert('settings', ['site_id' => $this->a, 'key' => 'phone', 'value' => '+225 01 02 03']);
        $this->db->insert('settings', ['site_id' => $this->a, 'key' => 'address', 'value' => 'Abidjan, Plateau']);
        $d = $this->jsonld($this->call('GET', '/', 'alpha.test')->body);
        $this->assertSame('Organization', $d['@type']);
        $this->assertSame('Site alpha', $d['name']);
        $this->assertSame('http://alpha.test', $d['url']);
        $this->assertSame('+225 01 02 03', $d['telephone']);
        $this->assertSame('CI', $d['address']['addressCountry']);
        $this->assertArrayNotHasKey('email', $d); // unset settings are omitted, never guessed
        $this->assertArrayNotHasKey('telephone', $this->jsonld($this->call('GET', '/', 'beta.test')->body));
    }

    public function testLandPageCarriesProductOfferAndCannotBreakOutOfTheScriptTag(): void
    {
        $this->makeLand($this->a, 'LD-1', ['title' => 'Lot </script><script>alert(1)</script> & "x"', 'price_xof' => 25000000, 'status' => 'reserved']);
        $this->makeLand($this->a, 'LD-2', ['status' => 'available']);
        $body = $this->call('GET', '/terrains/ld-1', 'alpha.test')->body;
        $this->assertStringNotContainsString('</script><script>alert(1)', $body);
        $d = $this->jsonld($body);
        $this->assertSame('Lot </script><script>alert(1)</script> & "x"', $d['name']); // survives intact once decoded
        $this->assertSame('25000000', $d['offers']['price']);
        $this->assertSame('XOF', $d['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/OutOfStock', $d['offers']['availability']);
        $this->assertSame('https://schema.org/InStock', $this->jsonld($this->call('GET', '/terrains/ld-2', 'alpha.test')->body)['offers']['availability']);
    }

    public function testSiteDeletionNeedsTheSlugTypedBackAndHidesTheSiteButKeepsData(): void
    {
        $this->superAdmin();
        $this->makeLand($this->a, 'KEEP');
        $this->call('POST', "/platform/sites/{$this->a}/delete", 'platform.test', ['confirm_slug' => 'wrong']);
        $this->assertSame('active', $this->db->value('SELECT status FROM sites WHERE id = ?', [$this->a]));
        $this->assertSame(200, $this->call('GET', '/', 'alpha.test')->status);
        $this->call('POST', "/platform/sites/{$this->a}/delete", 'platform.test', ['confirm_slug' => 'alpha']);
        $this->assertSame('deleted', $this->db->value('SELECT status FROM sites WHERE id = ?', [$this->a]));
        $this->assertSame(404, $this->call('GET', '/', 'alpha.test')->status);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM land_properties WHERE site_id = ?', [$this->a])); // data kept
        $this->assertSame(200, $this->call('GET', '/', 'beta.test')->status);
        $list = $this->call('GET', '/platform/sites', 'platform.test')->body;
        $this->assertStringContainsString('Supprimé', $list);
    }

    public function testSiteDeletionIsSuperAdminOnly(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $this->assertSame(404, $this->call('POST', "/platform/sites/{$this->b}/delete", 'alpha.test', ['confirm_slug' => 'beta'])->status);
        $this->assertSame('active', $this->db->value('SELECT status FROM sites WHERE id = ?', [$this->b]));
        $_SESSION = [];
        $this->login('platform.test', 'admin@alpha.test', area: 'platform');
        $this->call('POST', "/platform/sites/{$this->b}/delete", 'platform.test', ['confirm_slug' => 'beta']);
        $this->assertSame('active', $this->db->value('SELECT status FROM sites WHERE id = ?', [$this->b]));
    }

    public function testPurgeRefusesActiveSitesAndRemovesEverythingOfADeletedOneOnly(): void
    {
        $root = sys_get_temp_dir() . '/kib-purge-' . bin2hex(random_bytes(4));
        foreach ([$this->a, $this->b] as $id) {
            mkdir("$root/public/uploads/$id", 0755, true);
            mkdir("$root/storage/private/$id", 0755, true);
            file_put_contents("$root/public/uploads/$id/p.png", 'x');
            file_put_contents("$root/storage/private/$id/d.pdf", 'x');
        }
        $landA = $this->makeLand($this->a, 'A1');
        $this->db->insert('leads', ['site_id' => $this->a, 'reference' => 'LD-X1', 'type' => 'info', 'first_name' => 'J', 'last_name' => 'D', 'phone' => '0700000000', 'land_id' => $landA]); // composite FK that blocks a naive cascade
        $this->db->insert('pages', ['site_id' => $this->a, 'slug' => 'p', 'title' => 'P', 'status' => 'draft']);
        $this->makeLand($this->b, 'B1');
        $prov = new TenantProvisioner($this->db);

        try {
            $prov->purge($this->a, $root);
            $this->fail('purged an active site');
        } catch (\DomainException) {
        }
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM leads'));

        $this->db->run("UPDATE sites SET status = 'deleted' WHERE id = ?", [$this->a]);
        $prov->purge($this->a, $root);
        $this->assertNull($this->db->value('SELECT id FROM sites WHERE id = ?', [$this->a]));
        foreach (['leads', 'land_properties', 'pages', 'domains', 'site_members', 'land_categories'] as $t) {
            $this->assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM `$t` WHERE site_id = ?", [$this->a]), $t);
        }
        $this->assertDirectoryDoesNotExist("$root/public/uploads/{$this->a}");
        $this->assertDirectoryDoesNotExist("$root/storage/private/{$this->a}");
        // the other site is intact: rows and files
        $this->assertSame('B1', $this->db->value('SELECT reference FROM land_properties WHERE site_id = ?', [$this->b]));
        $this->assertSame(9, (int) $this->db->value('SELECT COUNT(*) FROM land_categories WHERE site_id = ?', [$this->b]));
        $this->assertFileExists("$root/public/uploads/{$this->b}/p.png");
        $this->assertSame(1, (int) $this->db->value("SELECT @@FOREIGN_KEY_CHECKS")); // checks are back on
        exec('rm -rf ' . escapeshellarg($root));
    }

    // — audit journal

    public function testSuccessfulAdminWritesAreLoggedWithUserSiteAndIpButNotReadsFailuresOrAnonymousPosts(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $this->db->run('DELETE FROM activity_logs'); // ignore the login row itself
        $this->call('GET', '/admin/terrains', 'alpha.test');                                   // read: not logged
        $this->call('POST', '/admin/terrains', 'alpha.test', ['reference' => 'J1', 'title' => 'Lot', 'area_m2' => '10', 'price_xof' => '1', 'status' => 'available']);
        $this->call('POST', '/admin/terrains', 'alpha.test', ['reference' => 'J1', 'title' => 'Dup', 'area_m2' => '10', 'price_xof' => '1', 'status' => 'available']); // 422: not logged
        $this->call('POST', '/admin/utilisateurs', 'alpha.test', ['email' => 'x@alpha.test'], false);                                      // 419: not logged
        $rows = $this->db->all('SELECT * FROM activity_logs');
        $this->assertCount(1, $rows);
        $this->assertSame('POST /admin/terrains', $rows[0]['action']);
        $this->assertSame($this->a, (int) $rows[0]['site_id']);
        $this->assertSame('10.0.0.1', $rows[0]['ip']);
        $this->assertSame('admin@alpha.test', $this->db->value('SELECT email FROM users WHERE id = ?', [$rows[0]['user_id']]));
        $_SESSION = [];
        $this->call('POST', '/contact', 'alpha.test', ['first_name' => 'A']);                  // anonymous: not logged
        $this->assertCount(1, $this->db->all('SELECT * FROM activity_logs'));
    }

    public function testLoginAndPlatformActionsAreLoggedAndTheBodyIsNeverStored(): void
    {
        $this->login('alpha.test', 'admin@alpha.test', 'correct-horse-battery');
        $this->assertSame('POST /admin/login', $this->db->value('SELECT action FROM activity_logs ORDER BY id DESC LIMIT 1'));
        $this->superAdmin();
        $this->call('POST', "/platform/sites/{$this->b}/status", 'platform.test', ['status' => 'suspended']);
        $row = $this->db->one('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 1');
        $this->assertNull($row['site_id']);
        $this->assertSame("POST /platform/sites/{$this->b}/status", $row['action']);
        foreach ($this->db->all('SELECT CONCAT_WS(" ", action, ip) AS t FROM activity_logs') as $r) {
            $this->assertStringNotContainsString('correct-horse-battery', $r['t']);
        }
    }

    public function testJournalPageIsSiteScopedAndForAdminsOnly(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $this->call('POST', '/admin/categories', 'alpha.test', ['name' => 'Marqueur Alpha']);
        $this->assertStringContainsString('POST /admin/categories', $this->call('GET', '/admin/journal', 'alpha.test')->body);
        $_SESSION = [];
        $this->login('beta.test', 'admin@beta.test');
        $this->assertStringNotContainsString('POST /admin/categories', $this->call('GET', '/admin/journal', 'beta.test')->body);
        $uid = $this->db->insert('users', ['email' => 'ed@alpha.test', 'name' => 'Ed', 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value("SELECT id FROM roles WHERE slug = 'editor'")]);
        $_SESSION = [];
        $this->login('alpha.test', 'ed@alpha.test');
        $this->assertSame(403, $this->call('GET', '/admin/journal', 'alpha.test')->status);
    }

    // — land categories

    public function testCategoriesCanBeAddedAndOnlyUnusedOwnOnesDeleted(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $this->call('POST', '/admin/categories', 'alpha.test', ['name' => 'Zone Franche']);
        $this->call('POST', '/admin/categories', 'alpha.test', ['name' => 'Zone Franche']); // duplicate: refused, not a 500
        $this->call('POST', '/admin/categories', 'alpha.test', ['name' => '  ']);
        $this->assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM land_categories WHERE site_id = ? AND slug = 'zone-franche'", [$this->a]));
        $this->assertSame(10, (int) $this->db->value('SELECT COUNT(*) FROM land_categories WHERE site_id = ?', [$this->a]));
        $mine = (int) $this->db->value("SELECT id FROM land_categories WHERE slug = 'zone-franche' AND site_id = ?", [$this->a]);
        $this->makeLand($this->a, 'C1', ['category_id' => $mine]);
        $this->call('POST', "/admin/categories/$mine/delete", 'alpha.test');
        $this->assertNotNull($this->db->value('SELECT id FROM land_categories WHERE id = ?', [$mine])); // in use: kept
        $this->db->run('DELETE FROM land_properties');
        $this->call('POST', "/admin/categories/$mine/delete", 'alpha.test');
        $this->assertNull($this->db->value('SELECT id FROM land_categories WHERE id = ?', [$mine]));
        $theirs = (int) $this->db->value('SELECT id FROM land_categories WHERE site_id = ? LIMIT 1', [$this->b]);
        $this->assertSame(404, $this->call('POST', "/admin/categories/$theirs/delete", 'alpha.test')->status);
        $this->assertNotNull($this->db->value('SELECT id FROM land_categories WHERE id = ?', [$theirs]));
    }
}
