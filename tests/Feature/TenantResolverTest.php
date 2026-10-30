<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Multisite\{DomainManager, TenantResolver};
use Tests\DbTestCase;

final class TenantResolverTest extends DbTestCase
{
    public function testNormalizeHost(): void
    {
        $this->assertSame('example.com', DomainManager::normalize(' EXAMPLE.com.:8080 '));
        $this->assertSame('', DomainManager::normalize('bad host/../x'));
        $this->assertSame('', DomainManager::normalize(''));
    }

    public function testResolvesCustomDomainWwwAndSubdomain(): void
    {
        $id = $this->makeSite('kib', 'kibarou.test');
        $r = new TenantResolver($this->db);
        $this->assertSame($id, (int) $r->resolve('kibarou.test')['id']);
        $this->assertSame($id, (int) $r->resolve('www.kibarou.test')['id']);
        $this->assertSame($id, (int) $r->resolve('kib.cms.test')['id']);
        $this->assertNull($r->resolve('unknown.test'));
        $this->assertNull($r->resolve('nope.cms.test'));
        $this->assertNull($r->resolve(''));
    }

    public function testDomainBelongsToOneSiteOnly(): void
    {
        $this->makeSite('one', 'one.test');
        $two = $this->makeSite('two', 'two.test');
        $this->expectException(\DomainException::class);
        (new DomainManager($this->db))->attach($two, 'ONE.test');
    }

    public function testFailedProvisioningLeavesNothingBehind(): void
    {
        $this->makeSite('one', 'one.test');
        try {
            $this->makeSite('two', 'one.test'); // duplicate host -> rollback
            $this->fail('expected exception');
        } catch (\DomainException) {
        }
        $this->assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM sites WHERE slug = 'two'"));
        $this->assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM users WHERE email = 'admin@two.test'"));
    }
}
