<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\DbTestCase;

final class HttpTest extends DbTestCase
{
    use \Tests\HttpHelpers;

    private int $a;
    private int $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = $this->makeSite('alpha', 'alpha.test');
        $this->b = $this->makeSite('beta', 'beta.test');
    }

    public function testUnknownHostIs404AndSuspendedSiteIs503(): void
    {
        $this->assertSame(404, $this->call('GET', '/', 'nobody.test')->status);
        $this->db->run("UPDATE sites SET status = 'suspended' WHERE id = ?", [$this->a]);
        $this->assertSame(503, $this->call('GET', '/', 'alpha.test')->status);
        $this->assertSame(200, $this->call('GET', '/', 'beta.test')->status);
    }

    public function testAdminRequiresLoginThenWorks(): void
    {
        $this->assertSame('/admin/login', $this->call('GET', '/admin', 'alpha.test')->headers['Location']);
        $this->assertSame(302, $this->login('alpha.test', 'admin@alpha.test')->status);
        $this->assertSame(200, $this->call('GET', '/admin', 'alpha.test')->status);
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $res = $this->call('POST', '/admin/login', 'alpha.test', ['email' => 'admin@alpha.test', 'password' => 'correct-horse-battery'], false);
        $this->assertSame(419, $res->status);
    }

    public function testAdminOfOneSiteCannotEnterAnother(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        // Same session reused on the other host (worst case): membership is still checked per tenant.
        $this->assertSame(403, $this->call('GET', '/admin', 'beta.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/terrains', 'beta.test')->status);
    }

    public function testLoginOnWrongSiteIsRefusedWithGenericMessage(): void
    {
        $res = $this->login('beta.test', 'admin@alpha.test');
        $this->assertSame(401, $res->status);
        $this->assertStringContainsString('Identifiants incorrects', $res->body);
        $this->assertNull($_SESSION['user_id'] ?? null);
    }

    public function testIdorOnOtherTenantsLandIsBlocked(): void
    {
        $landB = $this->makeLand($this->b, 'B1');
        $this->login('alpha.test', 'admin@alpha.test');
        $this->assertSame(404, $this->call('POST', "/admin/terrains/$landB/delete", 'alpha.test')->status);
        $this->assertSame(404, $this->call('GET', "/admin/terrains/$landB/edit", 'alpha.test')->status);
        $this->assertSame(404, $this->call('POST', "/admin/terrains/$landB/status", 'alpha.test', ['status' => 'sold'])->status);
        $this->assertSame('available', $this->db->value('SELECT status FROM land_properties WHERE id = ?', [$landB]));
    }

    public function testBruteForceIsThrottled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(401, $this->login('alpha.test', 'admin@alpha.test', 'wrong')->status);
        }
        $res = $this->login('alpha.test', 'admin@alpha.test'); // correct password, but locked out
        $this->assertSame(401, $res->status);
        $this->assertStringContainsString('Trop de tentatives', $res->body);
    }

    public function testRoleWithoutPermissionGets403(): void
    {
        $uid = $this->db->insert('users', ['email' => 'ed@alpha.test', 'name' => 'Ed', 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value("SELECT id FROM roles WHERE slug='editor'")]);
        $this->login('alpha.test', 'ed@alpha.test');
        $this->assertSame(200, $this->call('GET', '/admin', 'alpha.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/terrains', 'alpha.test')->status);
    }

    public function testPlatformIsSuperAdminOnlyAndAbsentFromTenantHosts(): void
    {
        $this->db->insert('users', ['email' => 'root@cms.test', 'name' => 'Root', 'is_super_admin' => 1, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->assertSame(404, $this->call('GET', '/platform/sites', 'alpha.test')->status);
        $this->assertSame(401, $this->login('platform.test', 'admin@alpha.test', area: 'platform')->status);
        $this->login('alpha.test', 'admin@alpha.test'); // tenant admin session
        $this->assertSame(403, $this->call('GET', '/platform/sites', 'platform.test')->status);
        $this->login('platform.test', 'root@cms.test', area: 'platform');
        $res = $this->call('GET', '/platform/sites', 'platform.test');
        $this->assertSame(200, $res->status);
        $this->assertStringContainsString('alpha.test', $res->body);
    }

    public function testSuperAdminCanCreateSiteFromPlatform(): void
    {
        $this->db->insert('users', ['email' => 'root@cms.test', 'name' => 'Root', 'is_super_admin' => 1, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->login('platform.test', 'root@cms.test', area: 'platform');
        $res = $this->call('POST', '/platform/sites', 'platform.test', ['name' => 'Gamma', 'slug' => 'gamma', 'host' => 'gamma.test', 'admin_name' => 'G', 'admin_email' => 'g@gamma.test', 'password' => 'correct-horse-battery']);
        $this->assertSame(302, $res->status);
        $this->assertSame(200, $this->call('GET', '/', 'gamma.test')->status);
        $dup = $this->call('POST', '/platform/sites', 'platform.test', ['name' => 'Gamma2', 'slug' => 'gamma2', 'host' => 'gamma.test', 'admin_name' => 'G', 'admin_email' => 'g2@gamma.test', 'password' => 'correct-horse-battery']);
        $this->assertSame(422, $dup->status);
    }

    public function testPublicCatalogueHidesSoldUnpublishedAndOtherTenants(): void
    {
        $this->makeLand($this->a, 'A-OK');
        $this->makeLand($this->a, 'A-SOLD', ['status' => 'sold']);
        $this->makeLand($this->a, 'A-DRAFT', ['is_published' => 0]);
        $this->makeLand($this->b, 'B-OK');
        $body = $this->call('GET', '/terrains', 'alpha.test')->body;
        $this->assertStringContainsString('A-OK', $body);
        $this->assertStringNotContainsString('A-SOLD', $body);
        $this->assertStringNotContainsString('A-DRAFT', $body);
        $this->assertStringNotContainsString('B-OK', $body);
        $this->assertStringContainsString('A-SOLD', $this->call('GET', '/terrains', 'alpha.test', query: ['status' => 'sold'])->body);
        $this->assertSame(404, $this->call('GET', '/terrains/b-ok', 'alpha.test')->status);
        $this->assertSame(200, $this->call('GET', '/terrains/a-ok', 'alpha.test')->status);
    }

    public function testCatalogueFiltersAndSortRunOnServer(): void
    {
        $this->makeLand($this->a, 'CHEAP', ['price_xof' => 5000000, 'area_m2' => 300]);
        $this->makeLand($this->a, 'MID', ['price_xof' => 15000000, 'area_m2' => 600]);
        $this->makeLand($this->a, 'BIG', ['price_xof' => 40000000, 'area_m2' => 2000]);
        $body = $this->call('GET', '/terrains', 'alpha.test', query: ['min' => '10000000', 'max' => '20000000'])->body;
        $this->assertStringContainsString('MID', $body);
        $this->assertStringNotContainsString('CHEAP', $body);
        $this->assertStringNotContainsString('BIG', $body);
        $body = $this->call('GET', '/terrains', 'alpha.test', query: ['sort' => 'price_desc'])->body;
        $this->assertLessThan(strpos($body, 'CHEAP'), strpos($body, 'BIG'));
        $this->assertStringNotContainsString('CHEAP', $this->call('GET', '/terrains', 'alpha.test', query: ['area_min' => '1000'])->body);
    }

    public function testAdminCanCreateEditAndDeleteLand(): void
    {
        $this->login('alpha.test', 'admin@alpha.test');
        $res = $this->call('POST', '/admin/terrains', 'alpha.test', ['reference' => 'KIB-001', 'title' => 'Lot test', 'area_m2' => '400', 'price_xof' => '12000000', 'status' => 'available', 'is_published' => '1']);
        $this->assertSame(302, $res->status);
        $row = $this->db->one("SELECT * FROM land_properties WHERE reference = 'KIB-001'");
        $this->assertSame($this->a, (int) $row['site_id']);
        $this->assertSame(422, $this->call('POST', '/admin/terrains', 'alpha.test', ['reference' => 'KIB-001', 'title' => 'Dup', 'area_m2' => '1', 'price_xof' => '1', 'status' => 'available'])->status);
        $this->call('POST', "/admin/terrains/{$row['id']}", 'alpha.test', ['reference' => 'KIB-001', 'title' => 'Lot renommé', 'area_m2' => '400', 'price_xof' => '12000000', 'status' => 'sold']);
        $this->assertSame('sold', $this->db->value('SELECT status FROM land_properties WHERE id = ?', [$row['id']]));
        $this->call('POST', "/admin/terrains/{$row['id']}/delete", 'alpha.test');
        $this->assertNull($this->db->one('SELECT id FROM land_properties WHERE id = ?', [$row['id']]));
    }
}
