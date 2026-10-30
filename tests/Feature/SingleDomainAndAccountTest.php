<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\{DbTestCase, HttpHelpers};

final class SingleDomainAndAccountTest extends DbTestCase
{
    use HttpHelpers;

    /** One-domain install: PLATFORM_HOST ("platform.test") is also the domain of the only site. */
    public function testPlatformHostThatIsAlsoASiteDomainServesBothWithoutMixingThem(): void
    {
        $this->makeSite('solo', 'platform.test');
        $this->makeLand($this->db->value("SELECT id FROM sites WHERE slug = 'solo'") ? (int) $this->db->value("SELECT id FROM sites WHERE slug = 'solo'") : 0, 'S1');
        $this->assertStringContainsString('Site solo', $this->call('GET', '/', 'platform.test')->body);       // the public site, not a 404
        $this->assertSame(200, $this->call('GET', '/terrains', 'platform.test')->status);
        $this->assertStringContainsString('Administration centrale', $this->call('GET', '/platform/login', 'platform.test')->body);
        $this->assertStringContainsString('Administration du site', $this->call('GET', '/admin/login', 'platform.test')->body);
        $this->assertSame(200, $this->call('GET', '/sitemap.xml', 'platform.test')->status);

        // a site admin is not a platform admin, even on the shared host
        $this->login('platform.test', 'admin@solo.test');
        $this->assertSame(200, $this->call('GET', '/admin', 'platform.test')->status);
        $this->assertSame(403, $this->call('GET', '/platform/sites', 'platform.test')->status);
        $this->assertSame(404, $this->call('GET', '/platform/sites', 'solo-other.test')->status);

        // and a super admin reaches both
        $this->db->insert('users', ['email' => 'root@cms.test', 'name' => 'Root', 'is_super_admin' => 1, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $_SESSION = [];
        $this->login('platform.test', 'root@cms.test', area: 'platform');
        $this->assertSame(200, $this->call('GET', '/platform/sites', 'platform.test')->status);
        $this->assertSame(200, $this->call('GET', '/admin', 'platform.test')->status);
    }

    public function testPlatformHostWithNoSiteStaysPlatformOnly(): void
    {
        $this->assertSame(404, $this->call('GET', '/', 'platform.test')->status);
        $this->assertSame(200, $this->call('GET', '/platform/login', 'platform.test')->status);
    }

    private function mk(): array
    {
        $a = $this->makeSite('alpha', 'alpha.test');
        $this->login('alpha.test', 'admin@alpha.test');
        return [$a];
    }

    private function change(array $over = [], string $host = 'alpha.test', string $path = '/admin/compte')
    {
        return $this->call('POST', $path, $host, $over + ['current' => 'correct-horse-battery', 'new' => 'a-brand-new-long-password', 'confirm' => 'a-brand-new-long-password']);
    }

    public function testChangingMyPasswordWorksAndTheOldOneStopsWorking(): void
    {
        $this->mk();
        $this->assertSame(200, $this->call('GET', '/admin/compte', 'alpha.test')->status);
        $this->assertSame(302, $this->change()->status);
        $_SESSION = [];
        $this->assertSame(401, $this->login('alpha.test', 'admin@alpha.test', 'correct-horse-battery')->status);
        $this->assertSame(302, $this->login('alpha.test', 'admin@alpha.test', 'a-brand-new-long-password')->status);
    }

    public function testBadChangesAreRefusedAndLeaveThePasswordUntouched(): void
    {
        $this->mk();
        $hash = $this->db->value("SELECT password_hash FROM users WHERE email = 'admin@alpha.test'");
        foreach ([['current' => 'wrong'], ['new' => 'short', 'confirm' => 'short'], ['confirm' => 'different-long-password'], ['new' => 'correct-horse-battery', 'confirm' => 'correct-horse-battery']] as $bad) {
            $this->assertSame(422, $this->change($bad)->status, json_encode($bad));
        }
        $this->assertSame($hash, $this->db->value("SELECT password_hash FROM users WHERE email = 'admin@alpha.test'"));
    }

    public function testWrongCurrentPasswordIsThrottledLikeALogin(): void
    {
        $this->mk();
        for ($i = 0; $i < 5; $i++) {
            $this->change(['current' => 'wrong']);
        }
        $res = $this->change(); // right password, but locked out
        $this->assertSame(422, $res->status);
        $this->assertStringContainsString('Trop de tentatives', $res->body);
        $this->assertTrue(password_verify('correct-horse-battery', (string) $this->db->value("SELECT password_hash FROM users WHERE email = 'admin@alpha.test'")));
    }

    public function testAccountPageNeedsLoginAndCsrfAndSuperAdminsUseThePlatformOne(): void
    {
        $this->makeSite('alpha', 'alpha.test');
        $this->assertSame(302, $this->call('GET', '/admin/compte', 'alpha.test')->status);
        $this->login('alpha.test', 'admin@alpha.test');
        $this->assertSame(419, $this->call('POST', '/admin/compte', 'alpha.test', ['current' => 'x', 'new' => 'y', 'confirm' => 'y'], false)->status);
        $this->db->insert('users', ['email' => 'root@cms.test', 'name' => 'Root', 'is_super_admin' => 1, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $_SESSION = [];
        $this->login('platform.test', 'root@cms.test', area: 'platform');
        $this->assertSame(302, $this->change(['new' => 'root-new-long-password', 'confirm' => 'root-new-long-password'], 'platform.test', '/platform/compte')->status);
        $this->assertTrue(password_verify('root-new-long-password', (string) $this->db->value("SELECT password_hash FROM users WHERE email = 'root@cms.test'")));
        $_SESSION = [];
        $this->login('alpha.test', 'admin@alpha.test');
        $this->assertSame(404, $this->call('GET', '/platform/compte', 'alpha.test')->status); // not on tenant hosts
    }
}
