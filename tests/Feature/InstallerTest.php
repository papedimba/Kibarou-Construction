<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Env;
use App\Multisite\TenantResolver;
use App\Services\Installer;
use Tests\DbTestCase;

final class InstallerTest extends DbTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/kib-install-' . bin2hex(random_bytes(4));
        mkdir("$this->root/vendor", 0755, true);
        touch("$this->root/vendor/autoload.php");
        mkdir("$this->root/database", 0755, true);
        symlink(dirname(__DIR__, 2) . '/database/migrations', "$this->root/database/migrations");
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    private function input(array $over = []): array
    {
        return $over + [
            'db_host' => Env::get('DB_HOST'), 'db_port' => Env::get('DB_PORT'), 'db_name' => Env::get('DB_NAME'), 'db_user' => Env::get('DB_USER'), 'db_pass' => Env::get('DB_PASS'),
            'platform_host' => 'Plateforme.Example.test:443', 'base_domain' => 'example.test', 'admin_name' => 'Root', 'admin_email' => 'Root@Example.test', 'admin_password' => 'a-very-long-passphrase',
        ];
    }

    public function testRequirementsPassOnAWritableTree(): void
    {
        $inst = new Installer($this->root);
        $this->assertTrue($inst->ready());
        $labels = array_column($inst->requirements(), 0);
        $this->assertContains('PHP 8.2 ou supérieur', $labels);
        $this->assertContains('Écriture du fichier .env', $labels);
    }

    public function testRefusesWhenAlreadyConfiguredOrLocked(): void
    {
        file_put_contents("$this->root/.env", 'x');
        $this->assertTrue((new Installer($this->root))->locked());
        $this->expectException(\InvalidArgumentException::class);
        (new Installer($this->root))->install($this->input());
    }

    public function testInvalidInputIsRejectedAndNothingIsWritten(): void
    {
        $inst = new Installer($this->root);
        foreach ([['admin_password' => 'short'], ['admin_email' => 'nope'], ['platform_host' => 'bad host/..'], ['base_domain' => '::'], ['db_name' => 'a;drop'], ['db_user' => ''], ['db_host' => 'h o s t'], ['db_port' => 'x']] as $bad) {
            try {
                $inst->install($this->input($bad));
                $this->fail('accepted ' . json_encode($bad));
            } catch (\InvalidArgumentException) {
            }
        }
        $this->assertFileDoesNotExist("$this->root/.env");
        $this->assertFileDoesNotExist("$this->root/storage/installed.lock");
    }

    public function testWrongDatabasePasswordGivesAFriendlyMessageWithoutLeakingIt(): void
    {
        try {
            (new Installer($this->root))->install($this->input(['db_pass' => 'S3cret-wrong-pass']));
            $this->fail('expected failure');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Connexion à la base impossible', $e->getMessage());
            $this->assertStringNotContainsString('S3cret-wrong-pass', $e->getMessage());
        }
        $this->assertFileDoesNotExist("$this->root/.env");
    }

    public function testSuccessfulInstallWritesSecureEnvLockAndSuperAdmin(): void
    {
        $inst = new Installer($this->root);
        $inst->install($this->input());
        $env = (string) file_get_contents("$this->root/.env");
        $this->assertSame('0600', substr(sprintf('%o', fileperms("$this->root/.env")), -4));
        $this->assertStringContainsString("APP_ENV=production\nAPP_DEBUG=0\n", $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=[0-9a-f]{48}$/m', $env);
        $this->assertStringContainsString('PLATFORM_HOST=plateforme.example.test', $env); // normalized
        $this->assertStringContainsString('PLATFORM_BASE_DOMAIN=example.test', $env);
        $this->assertFileExists("$this->root/storage/installed.lock");
        $u = $this->db->one("SELECT * FROM users WHERE email = 'root@example.test'");
        $this->assertSame(1, (int) $u['is_super_admin']);
        $this->assertTrue(password_verify('a-very-long-passphrase', $u['password_hash']));
        $this->assertTrue($inst->locked());
        $this->expectException(\InvalidArgumentException::class);
        $inst->install($this->input());
    }

    public function testOptionalFirstSiteIsProvisioned(): void
    {
        (new Installer($this->root))->install($this->input([
            'site_name' => 'Kibarou', 'site_slug' => 'kibarou', 'site_host' => 'kibarou.example.test',
            'site_admin_email' => 'admin@kibarou.example.test', 'site_admin_name' => 'Admin', 'site_admin_password' => 'another-long-passphrase',
        ]));
        $site = (new TenantResolver($this->db))->resolve('kibarou.example.test');
        $this->assertSame('Kibarou', $site['name']);
        $this->assertSame(9, (int) $this->db->value('SELECT COUNT(*) FROM land_categories WHERE site_id = ?', [$site['id']]));
    }
}
