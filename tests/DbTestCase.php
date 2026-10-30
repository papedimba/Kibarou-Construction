<?php
declare(strict_types=1);

namespace Tests;

use App\Core\{Container, Database};
use App\Multisite\{TenantContext, TenantProvisioner};
use PHPUnit\Framework\TestCase;

abstract class DbTestCase extends TestCase
{
    protected Database $db;
    protected Container $c;

    protected function setUp(): void
    {
        $this->db = new Database();
        $this->db->run('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['sites', 'domains', 'users', 'site_members', 'settings', 'activity_logs', 'login_attempts', 'land_categories', 'locations', 'land_properties'] as $t) {
            $this->db->run("TRUNCATE `$t`");
        }
        $this->db->run('SET FOREIGN_KEY_CHECKS = 1');
        $_SESSION = [];
        $this->c = new Container();
        $this->c->set(Database::class, $this->db);
        $this->c->set(TenantContext::class, new TenantContext());
    }

    protected function makeSite(string $slug, string $host): int
    {
        return (new TenantProvisioner($this->db))->create("Site $slug", $slug, $host, "admin@$slug.test", "Admin $slug", 'correct-horse-battery');
    }

    protected function makeLand(int $siteId, string $ref, array $over = []): int
    {
        return $this->db->insert('land_properties', $over + [
            'site_id' => $siteId, 'reference' => $ref, 'slug' => strtolower($ref), 'title' => "Terrain $ref",
            'area_m2' => 500, 'price_xof' => 10000000, 'status' => 'available', 'is_published' => 1,
        ]);
    }
}
