<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Multisite\TenantContext;
use App\Repositories\{LandDocumentRepository, MediaRepository};
use App\Services\{DocumentService, MediaService};
use Tests\{DbTestCase, HttpHelpers};

final class LandBtpTest extends DbTestCase
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

    protected function tearDown(): void
    {
        foreach ([$this->a, $this->b] as $id) { // keep the working tree free of test files
            foreach (['storage/private', 'public/uploads'] as $base) {
                foreach (glob(dirname(__DIR__, 2) . "/$base/$id/*") ?: [] as $f) {
                    @unlink($f);
                }
            }
        }
    }

    private function ctx(int $siteId): TenantContext
    {
        $c = new TenantContext();
        $c->set(['id' => $siteId, 'name' => "Site $siteId"]);
        return $c;
    }

    private function docs(int $siteId): DocumentService
    {
        $c = $this->ctx($siteId);
        return new DocumentService(new LandDocumentRepository($this->db, $c), $c);
    }

    private function images(int $siteId): MediaService
    {
        $c = $this->ctx($siteId);
        return new MediaService(new MediaRepository($this->db, $c), $c);
    }

    private function tmp(string $content): array
    {
        $f = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($f, $content);
        return ['name' => 'upload.bin', 'tmp_name' => $f, 'error' => UPLOAD_ERR_OK];
    }

    private function png(): array
    {
        $f = tempnam(sys_get_temp_dir(), 'png');
        imagepng(imagecreatetruecolor(8, 8), $f);
        return ['name' => 'p.png', 'tmp_name' => $f, 'error' => UPLOAD_ERR_OK];
    }

    private function pdfFile(): array
    {
        return $this->tmp("%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }

    private function role(string $slug, string $host = 'alpha.test'): void
    {
        $uid = $this->db->insert('users', ['email' => "$slug@alpha.test", 'name' => $slug, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
        $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value('SELECT id FROM roles WHERE slug = ?', [$slug])]);
        $this->login($host, "$slug@alpha.test");
    }

    public function testLocationFilterIncludesDescendantsAndIsTenantScoped(): void
    {
        $ville = $this->db->insert('locations', ['site_id' => $this->a, 'type' => 'ville', 'name' => 'Abidjan']);
        $quartier = $this->db->insert('locations', ['site_id' => $this->a, 'parent_id' => $ville, 'type' => 'quartier', 'name' => 'Cocody']);
        $other = $this->db->insert('locations', ['site_id' => $this->a, 'type' => 'ville', 'name' => 'Bouaké']);
        $foreign = $this->db->insert('locations', ['site_id' => $this->b, 'type' => 'ville', 'name' => 'Ailleurs']);
        $this->makeLand($this->a, 'IN-QUARTIER', ['location_id' => $quartier]);
        $this->makeLand($this->a, 'ELSEWHERE', ['location_id' => $other]);
        $body = $this->call('GET', '/terrains', 'alpha.test', query: ['location' => (string) $ville])->body;
        $this->assertStringContainsString('IN-QUARTIER', $body);
        $this->assertStringNotContainsString('ELSEWHERE', $body);
        $this->assertStringNotContainsString('IN-QUARTIER', $this->call('GET', '/terrains', 'alpha.test', query: ['location' => (string) $foreign])->body);
        $this->assertStringContainsString('Abidjan › Cocody', $this->call('GET', '/terrains/in-quartier', 'alpha.test')->body);
    }

    public function testAdminCannotAssignAnotherTenantsLocationOrCategory(): void
    {
        $foreign = $this->db->insert('locations', ['site_id' => $this->b, 'type' => 'ville', 'name' => 'Ailleurs']);
        $res = $this->call('POST', '/admin/terrains', 'alpha.test', ['reference' => 'X1', 'title' => 'T', 'area_m2' => '100', 'price_xof' => '1', 'status' => 'available', 'location_id' => (string) $foreign]);
        $this->assertSame(422, $res->status);
        $this->assertSame(422, $this->call('POST', '/admin/terrains', 'alpha.test', ['reference' => 'X2', 'title' => 'T', 'area_m2' => '100', 'price_xof' => '1', 'status' => 'available', 'latitude' => '99', 'longitude' => '1'])->status);
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM land_properties'));
    }

    public function testPrivateDocumentIsStoredOutsidePublicAndNeverServedPublicly(): void
    {
        $land = $this->makeLand($this->a, 'DOC1');
        $this->assertNull($this->docs($this->a)->store($this->pdfFile(), $land, 'acd', '2024-05-01', 'private', false));
        $doc = $this->db->one('SELECT * FROM land_documents');
        $file = $this->docs($this->a)->path($doc);
        $this->assertFileExists($file);
        $this->assertStringNotContainsString('/public/', $file);
        $this->assertSame(404, $this->call('GET', "/terrains/doc1/documents/{$doc['id']}", 'alpha.test')->status);
        $this->assertStringNotContainsString('documents/', $this->call('GET', '/terrains/doc1', 'alpha.test')->body);
        $admin = $this->call('GET', "/admin/terrains/$land/documents/{$doc['id']}", 'alpha.test');
        $this->assertSame(200, $admin->status);
        $this->assertSame('application/pdf', $admin->headers['Content-Type']);
        $this->assertStringContainsString('attachment', $admin->headers['Content-Disposition']);
    }

    public function testPublicDocumentIsServedAndVerificationIsNeverAssumed(): void
    {
        $land = $this->makeLand($this->a, 'DOC2');
        $this->docs($this->a)->store($this->pdfFile(), $land, 'titre_foncier', '', 'public', false);
        $id = (int) $this->db->value('SELECT id FROM land_documents');
        $page = $this->call('GET', '/terrains/doc2', 'alpha.test')->body;
        $this->assertStringContainsString('en attente de vérification', $page);
        $this->assertStringNotContainsString('document vérifié', $page);
        $this->assertSame(200, $this->call('GET', "/terrains/doc2/documents/$id", 'alpha.test')->status);
        $this->call('POST', "/admin/terrains/$land/documents/$id/verify", 'alpha.test', ['verification' => 'verified']);
        $this->assertStringContainsString('document vérifié', $this->call('GET', '/terrains/doc2', 'alpha.test')->body);
        $this->db->run('UPDATE land_properties SET is_published = 0 WHERE id = ?', [$land]);
        $this->assertSame(404, $this->call('GET', "/terrains/doc2/documents/$id", 'alpha.test')->status);
    }

    public function testDocumentUploadRejectsScriptsAndIsIsolatedBetweenTenants(): void
    {
        $land = $this->makeLand($this->a, 'DOC3');
        $svc = $this->docs($this->a);
        $this->assertNotNull($svc->store($this->tmp('<?php echo 1;'), $land, 'acd', '', 'private', false));
        $this->assertNotNull($svc->store($this->pdfFile(), $land, 'nope', '', 'private', false));
        $this->assertNotNull($svc->store($this->pdfFile(), $land, 'acd', '01/05/2024', 'private', false));
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM land_documents'));
        $this->assertNull($svc->store($this->pdfFile(), $land, 'plan', '', 'private', false));
        $id = (int) $this->db->value('SELECT id FROM land_documents');
        $this->login('beta.test', 'admin@beta.test');
        $this->assertSame(404, $this->call('GET', "/admin/terrains/$land/documents/$id", 'beta.test')->status);
        $this->assertSame(404, $this->call('POST', "/admin/terrains/$land/documents/$id/delete", 'beta.test')->status);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM land_documents'));
        $this->login('alpha.test', 'admin@alpha.test');
        $file = $svc->path($this->db->one('SELECT * FROM land_documents'));
        $this->call('POST', "/admin/terrains/$land/delete", 'alpha.test');
        $this->assertFileDoesNotExist($file);
    }

    public function testDocumentOfAnotherLandCannotBeReachedThroughMyLandUrl(): void
    {
        $l1 = $this->makeLand($this->a, 'L1');
        $l2 = $this->makeLand($this->a, 'L2');
        $this->docs($this->a)->store($this->pdfFile(), $l2, 'acd', '', 'private', false);
        $id = (int) $this->db->value('SELECT id FROM land_documents');
        $this->assertSame(404, $this->call('GET', "/admin/terrains/$l1/documents/$id", 'alpha.test')->status);
    }

    public function testPhotosAppearOnCatalogueAndFicheAndAreTenantScoped(): void
    {
        $land = $this->makeLand($this->a, 'PH1');
        [$mid, $err] = $this->images($this->a)->storeImage($this->png(), 'Vue', false);
        $this->assertNull($err);
        (new \App\Repositories\LandImageRepository($this->db, $this->ctx($this->a)))->attach($land, $mid);
        $name = $this->db->value('SELECT filename FROM media WHERE id = ?', [$mid]);
        $this->assertStringContainsString("/uploads/{$this->a}/$name", $this->call('GET', '/terrains', 'alpha.test')->body);
        $this->assertStringContainsString("/uploads/{$this->a}/$name", $this->call('GET', '/terrains/ph1', 'alpha.test')->body);
        $this->expectException(\PDOException::class); // DB refuses a link to another tenant's media
        $otherLand = $this->makeLand($this->b, 'PH2');
        (new \App\Repositories\LandImageRepository($this->db, $this->ctx($this->b)))->attach($otherLand, $mid);
    }

    public function testQrCodeAndPdfSheet(): void
    {
        $land = $this->makeLand($this->a, 'QR1', ['description' => 'Terrain plat, accès route bitumée.']);
        $svg = $this->call('GET', "/admin/terrains/$land/qr.svg", 'alpha.test');
        $this->assertSame('image/svg+xml', $svg->headers['Content-Type']);
        $this->assertStringContainsString('<svg', $svg->body);
        $png = $this->call('GET', "/admin/terrains/$land/qr.png", 'alpha.test');
        $this->assertSame("\x89PNG", substr($png->body, 0, 4));
        $pdf = $this->call('GET', "/admin/terrains/$land/fiche.pdf", 'alpha.test');
        $this->assertSame('application/pdf', $pdf->headers['Content-Type']);
        $this->assertSame('%PDF', substr($pdf->body, 0, 4));
        $this->assertSame('%PDF', substr($this->call('GET', '/terrains/qr1/fiche.pdf', 'alpha.test')->body, 0, 4));
        $this->assertSame(404, $this->call('GET', '/terrains/qr1/fiche.pdf', 'beta.test')->status);
        $this->login('beta.test', 'admin@beta.test');
        $this->assertSame(404, $this->call('GET', "/admin/terrains/$land/qr.svg", 'beta.test')->status);
        $this->assertSame(404, $this->call('GET', "/admin/terrains/$land/fiche.pdf", 'beta.test')->status);
    }

    public function testPdfIgnoresLogoPathOutsidePublicAndSettingsRejectDotDot(): void
    {
        $land = $this->makeLand($this->a, 'LOGO1');
        $this->db->insert('settings', ['site_id' => $this->a, 'key' => 'logo_url', 'value' => '/assets/../../.env']);
        $this->assertSame('%PDF', substr($this->call('GET', "/admin/terrains/$land/fiche.pdf", 'alpha.test')->body, 0, 4));
        $this->assertSame(422, $this->call('POST', '/admin/settings', 'alpha.test', ['logo_url' => '/assets/../../.env', 'theme' => 'corporate-premium'])->status);
        $this->db->run("UPDATE settings SET value = '/assets/images/brand/kibarou-logo-original.jpg' WHERE `key` = 'logo_url'");
        $with = $this->call('GET', "/admin/terrains/$land/fiche.pdf", 'alpha.test')->body;
        $this->assertSame('%PDF', substr($with, 0, 4));
        $this->assertGreaterThan(20000, strlen($with)); // the real logo is embedded
    }

    public function testSimilarLandsShareCategoryAndSkipSoldOnes(): void
    {
        $cat = (int) $this->db->value('SELECT id FROM land_categories WHERE site_id = ? LIMIT 1', [$this->a]);
        $this->makeLand($this->a, 'MAIN', ['category_id' => $cat]);
        $this->makeLand($this->a, 'SAMECAT', ['category_id' => $cat]);
        $this->makeLand($this->a, 'SOLDCAT', ['category_id' => $cat, 'status' => 'sold']);
        $this->makeLand($this->a, 'NOCAT');
        $body = $this->call('GET', '/terrains/main', 'alpha.test')->body;
        $this->assertStringContainsString('Terrain SAMECAT', $body);
        $this->assertStringNotContainsString('Terrain SOLDCAT', $body);
        $this->assertStringNotContainsString('Terrain NOCAT', $body);
    }

    public function testDevelopmentsAndPlotsAreIsolatedAndPublishedOnly(): void
    {
        $res = $this->call('POST', '/admin/lotissements', 'alpha.test', ['name' => 'Cité Verte', 'is_published' => '1']);
        $this->assertSame(302, $res->status);
        $dev = (int) $this->db->value("SELECT id FROM land_developments WHERE slug = 'cite-verte'");
        $this->call('POST', "/admin/lotissements/$dev/parcelles", 'alpha.test', ['number' => '12', 'reference' => 'CV-12', 'area_m2' => '400', 'price_xof' => '9000000', 'status' => 'available']);
        $this->call('POST', "/admin/lotissements/$dev/parcelles", 'alpha.test', ['number' => '13', 'reference' => 'CV-12', 'area_m2' => '400', 'price_xof' => '9000000', 'status' => 'available']);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM land_plots'));
        $this->assertStringContainsString('CV-12', $this->call('GET', '/lotissements/cite-verte', 'alpha.test')->body);
        $this->assertSame(404, $this->call('GET', '/lotissements/cite-verte', 'beta.test')->status);
        $this->db->run('UPDATE land_developments SET is_published = 0');
        $this->assertSame(404, $this->call('GET', '/lotissements/cite-verte', 'alpha.test')->status);
        // same plot reference is free on another tenant
        $this->login('beta.test', 'admin@beta.test');
        $this->call('POST', '/admin/lotissements', 'beta.test', ['name' => 'Autre']);
        $devB = (int) $this->db->value('SELECT id FROM land_developments WHERE site_id = ?', [$this->b]);
        $this->call('POST', "/admin/lotissements/$devB/parcelles", 'beta.test', ['number' => '1', 'reference' => 'CV-12', 'area_m2' => '1', 'price_xof' => '1', 'status' => 'available']);
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM land_plots'));
        // plot of dev A cannot be edited or deleted through tenant B
        $plotA = (int) $this->db->value('SELECT id FROM land_plots WHERE site_id = ?', [$this->a]);
        $this->assertSame(404, $this->call('POST', "/admin/lotissements/$devB/parcelles/$plotA/delete", 'beta.test')->status);
        $this->assertSame(404, $this->call('POST', "/admin/lotissements/$dev/parcelles/$plotA/delete", 'beta.test')->status);
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM land_plots'));
    }

    public function testNewServiceIsHiddenUntilActivatedAndIsPerTenant(): void
    {
        $this->call('POST', '/admin/services', 'alpha.test', ['title' => 'Terrassement', 'category' => 'terrassement', 'description' => 'Texte']);
        $this->assertSame(0, (int) $this->db->value('SELECT is_active FROM construction_services'));
        $this->assertStringNotContainsString('Terrassement', $this->call('GET', '/services', 'alpha.test')->body);
        $this->assertSame(404, $this->call('GET', '/services/terrassement', 'alpha.test')->status);
        $id = (int) $this->db->value('SELECT id FROM construction_services');
        $this->call('POST', "/admin/services/$id", 'alpha.test', ['title' => 'Terrassement', 'category' => 'terrassement', 'is_active' => '1']);
        $this->assertStringContainsString('Terrassement', $this->call('GET', '/services', 'alpha.test')->body);
        $this->assertSame(200, $this->call('GET', '/services/terrassement', 'alpha.test')->status);
        $this->assertStringNotContainsString('Terrassement', $this->call('GET', '/services', 'beta.test')->body);
        $this->assertSame(422, $this->call('POST', '/admin/services', 'alpha.test', ['title' => 'X', 'category' => 'inconnue'])->status);
    }

    public function testProjectsPublishOnlyWhenPublishedWithGalleryAndNothingPrefilled(): void
    {
        $this->assertStringContainsString('seront présentées', $this->call('GET', '/realisations', 'alpha.test')->body);
        $this->call('POST', '/admin/realisations', 'alpha.test', ['name' => 'Villa Test', 'category' => 'construction-maisons', 'status' => 'completed']);
        $id = (int) $this->db->value('SELECT id FROM projects');
        $this->assertSame(404, $this->call('GET', '/realisations/villa-test', 'alpha.test')->status);
        $this->call('POST', "/admin/realisations/$id", 'alpha.test', ['name' => 'Villa Test', 'category' => 'construction-maisons', 'status' => 'completed', 'is_published' => '1']);
        [$mid] = $this->images($this->a)->storeImage($this->png(), 'Façade', false);
        (new \App\Repositories\ProjectImageRepository($this->db, $this->ctx($this->a)))->attach($id, $mid);
        $name = $this->db->value('SELECT filename FROM media WHERE id = ?', [$mid]);
        $this->assertStringContainsString($name, $this->call('GET', '/realisations/villa-test', 'alpha.test')->body);
        $this->assertSame(404, $this->call('GET', '/realisations/villa-test', 'beta.test')->status);
        $this->login('beta.test', 'admin@beta.test');
        $this->assertSame(404, $this->call('POST', "/admin/realisations/$id/delete", 'beta.test')->status);
    }

    public function testRolesGateEachModule(): void
    {
        $this->role('btp_manager');
        $this->assertSame(200, $this->call('GET', '/admin/services', 'alpha.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/terrains/new', 'alpha.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/lotissements', 'alpha.test')->status);
        $this->role('land_manager');
        $this->assertSame(200, $this->call('GET', '/admin/lotissements', 'alpha.test')->status);
        $this->assertSame(200, $this->call('GET', '/admin/localisations', 'alpha.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/services', 'alpha.test')->status);
        $this->role('editor');
        $this->assertSame(403, $this->call('GET', '/admin/realisations', 'alpha.test')->status);
        $this->assertSame(403, $this->call('GET', '/admin/lotissements', 'alpha.test')->status);
    }

    public function testLocationsCanBeManagedAndInUseOnesAreProtected(): void
    {
        $this->call('POST', '/admin/localisations', 'alpha.test', ['type' => 'ville', 'name' => 'Yamoussoukro']);
        $ville = (int) $this->db->value('SELECT id FROM locations');
        $this->call('POST', '/admin/localisations', 'alpha.test', ['type' => 'quartier', 'name' => 'Habitat', 'parent_id' => (string) $ville]);
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM locations'));
        $this->call('POST', "/admin/localisations/$ville/delete", 'alpha.test'); // has a child: refused, not a 500
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM locations'));
        $this->call('POST', '/admin/localisations', 'alpha.test', ['type' => 'ville', 'name' => 'Pirate', 'parent_id' => '999999']);
        $this->assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM locations'));
    }

    public function testReservedSlugsCoverNewPublicSections(): void
    {
        foreach (['services', 'realisations', 'lotissements'] as $slug) {
            $this->assertSame(422, $this->call('POST', '/admin/pages', 'alpha.test', ['title' => 'x', 'slug' => $slug, 'status' => 'published'])->status);
        }
    }
}
