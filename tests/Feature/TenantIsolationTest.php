<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Multisite\TenantContext;
use App\Repositories\LandRepository;
use Tests\DbTestCase;

final class TenantIsolationTest extends DbTestCase
{
    private int $a;
    private int $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = $this->makeSite('alpha', 'alpha.test');
        $this->b = $this->makeSite('beta', 'beta.test');
    }

    private function repoFor(int $siteId): LandRepository
    {
        $ctx = new TenantContext();
        $ctx->set(['id' => $siteId]);
        return new LandRepository($this->db, $ctx);
    }

    public function testRepositoryCannotReadOtherTenantRow(): void
    {
        $landB = $this->makeLand($this->b, 'B1');
        $this->assertNull($this->repoFor($this->a)->find($landB));
        $this->assertNotNull($this->repoFor($this->b)->find($landB));
    }

    public function testRepositoryCannotUpdateOrDeleteOtherTenantRow(): void
    {
        $landB = $this->makeLand($this->b, 'B1');
        $repoA = $this->repoFor($this->a);
        $this->assertFalse($repoA->update($landB, ['title' => 'hacked']));
        $this->assertFalse($repoA->delete($landB));
        $this->assertSame('Terrain B1', $this->repoFor($this->b)->find($landB)['title']);
    }

    public function testSearchOnlyReturnsOwnRows(): void
    {
        $this->makeLand($this->a, 'A1');
        $this->makeLand($this->b, 'B1');
        $this->makeLand($this->b, 'B2');
        $r = $this->repoFor($this->a)->search([], false);
        $this->assertSame(1, $r['total']);
        $this->assertSame('A1', $r['items'][0]['reference']);
    }

    public function testClientSuppliedSiteIdIsIgnoredOnInsert(): void
    {
        $id = $this->repoFor($this->a)->insert([
            'site_id' => $this->b, 'reference' => 'X1', 'slug' => 'x1', 'title' => 'X', 'area_m2' => 100, 'price_xof' => 1,
        ]);
        $this->assertSame($this->a, (int) $this->db->value('SELECT site_id FROM land_properties WHERE id = ?', [$id]));
    }

    public function testDatabaseRejectsCrossTenantCategory(): void
    {
        $catB = (int) $this->db->value('SELECT id FROM land_categories WHERE site_id = ? LIMIT 1', [$this->b]);
        $this->expectException(\PDOException::class);
        $this->makeLand($this->a, 'A1', ['category_id' => $catB]);
    }

    public function testSameReferenceAllowedAcrossTenantsButNotWithin(): void
    {
        $this->makeLand($this->a, 'REF1');
        $this->makeLand($this->b, 'REF1');
        $this->expectException(\PDOException::class);
        $this->makeLand($this->a, 'REF1', ['slug' => 'other']);
    }
}
