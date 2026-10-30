<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Response;
use App\Services\{AntiSpam, FormDefinition, Mailer};
use Tests\{DbTestCase, HttpHelpers};

final class CrmTest extends DbTestCase
{
    use HttpHelpers;

    private int $a;
    private int $b;

    protected function setUp(): void
    {
        parent::setUp();
        @unlink(dirname(__DIR__, 2) . '/storage/logs/mail.log');
        $this->a = $this->makeSite('alpha', 'alpha.test');
        $this->b = $this->makeSite('beta', 'beta.test');
    }

    private function valid(array $over = []): array
    {
        return $over + ['type' => 'info', 'first_name' => 'Awa', 'last_name' => 'Koné', 'phone' => '+225 07 00 00 00 00', 'email' => 'awa@example.test', 'message' => 'Bonjour', 'consent' => '1'];
    }

    /** A human-looking public submission: valid signed token issued 10 s ago. */
    private function submit(string $host, array $fields, ?string $token = null, string $path = '/contact'): Response
    {
        $_SESSION = [];
        return $this->call('POST', $path, $host, $fields + ['_t' => $token ?? AntiSpam::token(time() - 10)]);
    }

    private function mailLog(): string
    {
        return @file_get_contents(dirname(__DIR__, 2) . '/storage/logs/mail.log') ?: '';
    }

    private function leadCount(?int $site = null): int
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM leads' . ($site ? " WHERE site_id = $site" : ''));
    }

    private function asAdmin(string $host, string $slug): void
    {
        $_SESSION = [];
        $this->login($host, "admin@$slug.test");
    }

    private function makeLead(int $site, array $over = []): int
    {
        static $n = 0;
        $n++;
        return $this->db->insert('leads', $over + ['site_id' => $site, 'reference' => 'LD-T' . $site . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'type' => 'info', 'first_name' => 'Jean', 'last_name' => "Nom$n", 'phone' => '0700000000', 'status' => 'new']);
    }

    public function testPublicRequestCreatesTenantLeadWithReferenceHistoryConsentAndNotification(): void
    {
        $this->db->insert('settings', ['site_id' => $this->a, 'key' => 'email', 'value' => 'contact@alpha.test']);
        $res = $this->submit('alpha.test', $this->valid());
        $this->assertSame(302, $res->status);
        $this->assertSame('/demandes/merci', $res->headers['Location']);
        $lead = $this->db->one('SELECT * FROM leads');
        $this->assertSame($this->a, (int) $lead['site_id']);
        $this->assertMatchesRegularExpression('/^LD-\d{6}$/', $lead['reference']);
        $this->assertSame('new', $lead['status']);
        $this->assertNotNull($lead['consent_at']);
        $this->assertSame('created', $this->db->value('SELECT action FROM lead_history WHERE lead_id = ?', [$lead['id']]));
        $this->assertStringContainsString($lead['reference'], $this->mailLog());
        $this->assertStringContainsString('contact@alpha.test', $this->mailLog());
        $this->assertSame(200, $this->call('GET', '/demandes/merci', 'alpha.test')->status);
    }

    public function testValidationRejectsBadInputAndStoresNothing(): void
    {
        foreach ([['consent' => ''], ['phone' => 'abc'], ['email' => 'not-an-email'], ['first_name' => ''], ['type' => 'quote'], ['message' => str_repeat('x', 2001)]] as $bad) {
            $this->assertSame(422, $this->submit('alpha.test', $this->valid($bad))->status, json_encode($bad));
        }
        $this->assertSame(0, $this->leadCount());
    }

    public function testAntispamRejectsBotsReplaysAndFloods(): void
    {
        $this->assertSame(429, $this->submit('alpha.test', $this->valid(['website' => 'http://spam']))->status);
        $this->assertSame(429, $this->submit('alpha.test', $this->valid(), AntiSpam::token(time()))->status);          // too fast
        $this->assertSame(429, $this->submit('alpha.test', $this->valid(), AntiSpam::token(time() - 86400))->status);   // expired
        $this->assertSame(429, $this->submit('alpha.test', $this->valid(), '123.forged')->status);
        $this->assertSame(429, $this->call('POST', '/contact', 'alpha.test', $this->valid())->status);                    // no token at all
        $this->assertSame(0, $this->leadCount());
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(302, $this->submit('alpha.test', $this->valid())->status);
        }
        $this->assertSame(429, $this->submit('alpha.test', $this->valid())->status);
        $this->assertSame(5, $this->leadCount());
    }

    public function testPublicFormNeedsCsrfToken(): void
    {
        $_SESSION = [];
        $this->assertSame(419, $this->call('POST', '/contact', 'alpha.test', $this->valid() + ['_t' => AntiSpam::token(time() - 10)], false)->status);
    }

    public function testLandAndServiceContextIsResolvedInsideTheTenantOnly(): void
    {
        $landA = $this->makeLand($this->a, 'A1');
        $this->makeLand($this->b, 'B1');
        $this->makeLand($this->a, 'DRAFT', ['is_published' => 0]);
        $svc = $this->db->insert('construction_services', ['site_id' => $this->a, 'category' => 'terrassement', 'title' => 'Terrassement', 'slug' => 'terrassement', 'is_active' => 1]);
        $this->db->insert('construction_services', ['site_id' => $this->a, 'category' => 'genie-civil', 'title' => 'Caché', 'slug' => 'cache', 'is_active' => 0]);

        $this->assertSame(302, $this->submit('alpha.test', $this->valid(['terrain' => 'a1', 'type' => 'visit', 'preferred_date' => '2030-01-15']))->status);
        $this->assertSame($landA, (int) $this->db->value('SELECT land_id FROM leads'));
        $this->assertSame(302, $this->submit('alpha.test', $this->valid(['service' => 'terrassement', 'type' => 'quote']))->status);
        $this->assertSame($svc, (int) $this->db->value("SELECT service_id FROM leads WHERE type = 'quote'"));

        $this->assertSame(422, $this->submit('alpha.test', $this->valid(['terrain' => 'b1']))->status);      // other tenant's land
        $this->assertSame(422, $this->submit('alpha.test', $this->valid(['terrain' => 'draft']))->status);   // unpublished
        $this->assertSame(422, $this->submit('alpha.test', $this->valid(['service' => 'cache', 'type' => 'quote']))->status); // inactive
        $this->assertSame(422, $this->submit('alpha.test', $this->valid(['terrain' => 'a1', 'type' => 'partnership']))->status);
        $this->assertSame(2, $this->leadCount());
        $this->assertStringContainsString('Demander des informations', $this->call('GET', '/terrains/a1', 'alpha.test')->body);
        $this->assertStringContainsString('A1', $this->call('GET', '/contact', 'alpha.test', query: ['terrain' => 'a1'])->body);
    }

    public function testWhatsappButtonOnlyWhenConfigured(): void
    {
        $this->makeLand($this->a, 'W1');
        $this->assertStringNotContainsString('wa.me', $this->call('GET', '/terrains/w1', 'alpha.test')->body);
        $this->db->insert('settings', ['site_id' => $this->a, 'key' => 'whatsapp', 'value' => '+225 07 00 00 00 00']);
        $this->assertStringContainsString('https://wa.me/2250700000000?text=', $this->call('GET', '/terrains/w1', 'alpha.test')->body);
    }

    public function testAdminCannotSeeOrTouchAnotherTenantsLeads(): void
    {
        $leadA = $this->makeLead($this->a, ['last_name' => 'SecretA']);
        $this->makeLead($this->b, ['last_name' => 'VisibleB']);
        $this->asAdmin('beta.test', 'beta');
        $list = $this->call('GET', '/admin/prospects', 'beta.test')->body;
        $this->assertStringContainsString('VisibleB', $list);
        $this->assertStringNotContainsString('SecretA', $list);
        $this->assertStringNotContainsString('SecretA', $this->call('GET', '/admin/prospects/export.csv', 'beta.test')->body);
        $this->assertSame(404, $this->call('GET', "/admin/prospects/$leadA", 'beta.test')->status);
        foreach (['status' => ['status' => 'converted'], 'notes' => ['body' => 'pwn'], 'visites' => ['scheduled_at' => '2030-01-01T10:00'], 'assign' => ['assigned_to' => ''], 'delete' => []] as $action => $post) {
            $this->assertSame(404, $this->call('POST', "/admin/prospects/$leadA/$action", 'beta.test', $post)->status, $action);
        }
        $row = $this->db->one('SELECT * FROM leads WHERE id = ?', [$leadA]);
        $this->assertSame('new', $row['status']);
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM lead_notes'));
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM appointments'));
    }

    public function testStatusChangesAreLoggedAndInvalidOnesIgnored(): void
    {
        $id = $this->makeLead($this->a);
        $this->asAdmin('alpha.test', 'alpha');
        $this->call('POST', "/admin/prospects/$id/status", 'alpha.test', ['status' => 'negotiating']);
        $this->call('POST', "/admin/prospects/$id/status", 'alpha.test', ['status' => 'negotiating']); // no-op
        $this->call('POST', "/admin/prospects/$id/status", 'alpha.test', ['status' => 'hacked']);
        $this->assertSame('negotiating', $this->db->value('SELECT status FROM leads WHERE id = ?', [$id]));
        $this->assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM lead_history WHERE lead_id = ? AND action = 'status'", [$id]));
        $page = $this->call('GET', "/admin/prospects/$id", 'alpha.test')->body;
        $this->assertStringContainsString('Nouveau → En négociation', $page);
    }

    public function testAssignmentOnlyToMembersOfTheSameSite(): void
    {
        $id = $this->makeLead($this->a);
        $this->asAdmin('alpha.test', 'alpha');
        $adminA = (int) $this->db->value("SELECT id FROM users WHERE email = 'admin@alpha.test'");
        $adminB = (int) $this->db->value("SELECT id FROM users WHERE email = 'admin@beta.test'");
        $this->call('POST', "/admin/prospects/$id/assign", 'alpha.test', ['assigned_to' => (string) $adminB]);
        $this->assertNull($this->db->value('SELECT assigned_to FROM leads WHERE id = ?', [$id]));
        $this->call('POST', "/admin/prospects/$id/assign", 'alpha.test', ['assigned_to' => (string) $adminA]);
        $this->assertSame($adminA, (int) $this->db->value('SELECT assigned_to FROM leads WHERE id = ?', [$id]));
        $this->assertStringContainsString('Admin alpha', $this->call('GET', '/admin/prospects', 'alpha.test', query: ['assigned' => (string) $adminA])->body);
    }

    public function testNotesAreEscapedAndBlankRejected(): void
    {
        $id = $this->makeLead($this->a);
        $this->asAdmin('alpha.test', 'alpha');
        $this->call('POST', "/admin/prospects/$id/notes", 'alpha.test', ['body' => '   ']);
        $this->call('POST', "/admin/prospects/$id/notes", 'alpha.test', ['body' => '<script>alert(1)</script>']);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM lead_notes'));
        $body = $this->call('GET', "/admin/prospects/$id", 'alpha.test')->body;
        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function testSchedulingAVisitCreatesAppointmentAndMovesStatus(): void
    {
        $id = $this->makeLead($this->a);
        $this->asAdmin('alpha.test', 'alpha');
        $this->call('POST', "/admin/prospects/$id/visites", 'alpha.test', ['scheduled_at' => 'demain']);
        $this->assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM appointments'));
        $this->call('POST', "/admin/prospects/$id/visites", 'alpha.test', ['scheduled_at' => '2030-03-04T09:30', 'note' => 'RDV sur place']);
        $this->assertSame('visit_scheduled', $this->db->value('SELECT status FROM leads WHERE id = ?', [$id]));
        $this->assertStringContainsString('RDV sur place', $this->call('GET', '/admin/visites', 'alpha.test')->body);
        $appt = (int) $this->db->value('SELECT id FROM appointments');
        $this->call('POST', "/admin/visites/$appt", 'alpha.test', ['status' => 'done']);
        $this->assertSame('done', $this->db->value('SELECT status FROM appointments'));
        $this->asAdmin('beta.test', 'beta');
        $this->assertSame(404, $this->call('POST', "/admin/visites/$appt", 'beta.test', ['status' => 'cancelled'])->status);
        $this->assertSame('done', $this->db->value('SELECT status FROM appointments'));
    }

    public function testSearchFiltersAndPagination(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->makeLead($this->a, ['phone' => '07000000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }
        $this->makeLead($this->a, ['last_name' => 'Unique', 'status' => 'converted', 'type' => 'quote', 'email' => 'u@x.test']);
        $this->asAdmin('alpha.test', 'alpha');
        $p1 = $this->call('GET', '/admin/prospects', 'alpha.test')->body;
        $this->assertStringContainsString('26 résultat(s)', $p1);
        $this->assertSame(20, substr_count($p1, '<td><a href="/admin/prospects/'));
        $this->assertSame(6, substr_count($this->call('GET', '/admin/prospects', 'alpha.test', query: ['page' => '2'])->body, '<td><a href="/admin/prospects/'));
        $this->assertStringContainsString('1 résultat(s)', $this->call('GET', '/admin/prospects', 'alpha.test', query: ['status' => 'converted'])->body);
        $this->assertStringContainsString('1 résultat(s)', $this->call('GET', '/admin/prospects', 'alpha.test', query: ['type' => 'quote'])->body);
        $this->assertStringContainsString('1 résultat(s)', $this->call('GET', '/admin/prospects', 'alpha.test', query: ['q' => 'u@x.test'])->body);
        $this->assertStringContainsString('1 résultat(s)', $this->call('GET', '/admin/prospects', 'alpha.test', query: ['q' => '0700000012'])->body);
        $this->assertStringContainsString('0 résultat(s)', $this->call('GET', '/admin/prospects', 'alpha.test', query: ['q' => "' OR 1=1 --"])->body);
    }

    public function testCsvExportRespectsFiltersAndNeutralisesFormulas(): void
    {
        $this->makeLead($this->a, ['last_name' => 'Normal', 'status' => 'new']);
        $this->makeLead($this->a, ['last_name' => '=HYPERLINK("http://evil")', 'status' => 'converted', 'message' => '+cmd|calc']);
        $this->asAdmin('alpha.test', 'alpha');
        $res = $this->call('GET', '/admin/prospects/export.csv', 'alpha.test', query: ['status' => 'converted']);
        $this->assertSame('text/csv; charset=utf-8', $res->headers['Content-Type']);
        $this->assertStringStartsWith("\xEF\xBB\xBFRéférence;Date", $res->body);
        $this->assertStringNotContainsString('Normal', $res->body);
        $this->assertStringContainsString("'=HYPERLINK", $res->body);
        $this->assertStringContainsString("'+cmd", $res->body);
        $this->assertStringNotContainsString(';=HYPERLINK', $res->body);
    }

    public function testRolesForCrm(): void
    {
        foreach (['sales_manager' => [200, 200], 'land_manager' => [403, 403], 'btp_manager' => [403, 403], 'editor' => [403, 403]] as $role => [$list, $visits]) {
            $uid = $this->db->insert('users', ['email' => "$role@alpha.test", 'name' => $role, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
            $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value('SELECT id FROM roles WHERE slug = ?', [$role])]);
            $_SESSION = [];
            $this->login('alpha.test', "$role@alpha.test");
            $this->assertSame($list, $this->call('GET', '/admin/prospects', 'alpha.test')->status, $role);
            $this->assertSame($visits, $this->call('GET', '/admin/visites', 'alpha.test')->status, $role);
            $this->assertSame($list, $this->call('GET', '/admin/prospects/export.csv', 'alpha.test')->status, "$role export");
        }
    }

    public function testLandWithLeadsCannotBeDeletedButCanBeMadeUnavailable(): void
    {
        $land = $this->makeLand($this->a, 'KEEP');
        $this->makeLead($this->a, ['land_id' => $land]);
        $this->asAdmin('alpha.test', 'alpha');
        $res = $this->call('POST', "/admin/terrains/$land/delete", 'alpha.test');
        $this->assertSame(302, $res->status);
        $this->assertNotNull($this->db->one('SELECT id FROM land_properties WHERE id = ?', [$land]));
        $this->call('POST', "/admin/terrains/$land/status", 'alpha.test', ['status' => 'unavailable']);
        $this->assertSame('unavailable', $this->db->value('SELECT status FROM land_properties WHERE id = ?', [$land]));
        $svc = $this->db->insert('construction_services', ['site_id' => $this->a, 'category' => 'autres', 'title' => 'S', 'slug' => 's', 'is_active' => 1]);
        $this->makeLead($this->a, ['service_id' => $svc, 'type' => 'quote']);
        $this->call('POST', "/admin/services/$svc/delete", 'alpha.test');
        $this->assertNotNull($this->db->one('SELECT id FROM construction_services WHERE id = ?', [$svc]));
    }

    public function testDatabaseRefusesALeadLinkedToAnotherTenantsLand(): void
    {
        $landB = $this->makeLand($this->b, 'B1');
        $this->expectException(\PDOException::class);
        $this->makeLead($this->a, ['land_id' => $landB]);
    }

    public function testDashboardShowsCrmFigures(): void
    {
        $this->makeLead($this->a);
        $this->makeLead($this->a, ['type' => 'quote', 'status' => 'contacted']);
        $this->makeLead($this->b);
        $this->asAdmin('alpha.test', 'alpha');
        $body = $this->call('GET', '/admin', 'alpha.test')->body;
        $this->assertStringContainsString('<strong>2</strong> demandes commerciales', $body);
        $this->assertStringContainsString('<strong>1</strong> demandes en attente', $body);
        $this->assertStringContainsString('<strong>1</strong> demandes de devis', $body);
    }

    public function testFormBuilderEndToEnd(): void
    {
        $this->asAdmin('alpha.test', 'alpha');
        $def = "Nom | text | 1\nEmail | email | 1\nBudget | select | 0 | 10M, 20M\nJ'accepte | checkbox | 1";
        $this->assertSame(302, $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'Partenaires', 'fields_text' => $def, 'notify_email' => 'boss@alpha.test', 'is_active' => '1', 'success_message' => 'Reçu !'])->status);
        $this->assertStringContainsString('Budget', $this->call('GET', '/f/partenaires', 'alpha.test')->body);
        $this->assertSame(404, $this->call('GET', '/f/partenaires', 'beta.test')->status);

        $ok = ['f0' => 'Awa', 'f1' => 'a@b.test', 'f2' => '20M', 'f3' => '1'];
        $this->assertSame(302, $this->submit('alpha.test', $ok, null, '/f/partenaires')->status);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM form_submissions'));
        $this->assertStringContainsString('boss@alpha.test', $this->mailLog());
        foreach ([['f0' => ''] + $ok, ['f1' => 'nope'] + $ok, ['f2' => '999M'] + $ok, ['f3' => ''] + $ok] as $bad) {
            $this->assertSame(422, $this->submit('alpha.test', $bad, null, '/f/partenaires')->status);
        }
        $this->assertSame(429, $this->submit('alpha.test', ['website' => 'x'] + $ok, null, '/f/partenaires')->status);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM form_submissions'));

        $id = (int) $this->db->value('SELECT id FROM forms');
        $this->asAdmin('alpha.test', 'alpha'); // submit() resets the session to act as an anonymous visitor
        $this->assertStringContainsString('Awa', $this->call('GET', "/admin/formulaires/$id/reponses", 'alpha.test')->body);
        $this->asAdmin('beta.test', 'beta');
        $this->assertSame(404, $this->call('GET', "/admin/formulaires/$id/reponses", 'beta.test')->status);
        $this->assertSame(404, $this->call('POST', "/admin/formulaires/$id/delete", 'beta.test')->status);
        $this->assertSame(404, $this->call('GET', "/admin/formulaires/$id/edit", 'beta.test')->status);
        $this->assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM forms'));
    }

    public function testFormBuilderRejectsBadDefinitionsAndInactiveFormsAreHidden(): void
    {
        $this->asAdmin('alpha.test', 'alpha');
        $this->assertSame(422, $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'X', 'fields_text' => 'Champ | magique | 1'])->status);
        $this->assertSame(422, $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'X', 'fields_text' => 'Liste | select | 1'])->status);
        $this->assertSame(422, $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'X', 'fields_text' => ''])->status);
        $this->assertSame(422, $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'X', 'fields_text' => 'Nom | text | 1', 'notify_email' => 'bad'])->status);
        $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'Cache', 'fields_text' => 'Nom | text | 1']); // is_active unchecked
        $this->assertSame(404, $this->call('GET', '/f/cache', 'alpha.test')->status);
        $this->assertSame(422, $this->call('POST', '/admin/formulaires', 'alpha.test', ['name' => 'Cache', 'fields_text' => 'Nom | text | 1'])->status); // duplicate slug
        $this->asAdmin('beta.test', 'beta');
        $this->assertSame(302, $this->call('POST', '/admin/formulaires', 'beta.test', ['name' => 'Cache', 'fields_text' => 'Nom | text | 1'])->status); // same slug, other tenant
    }

    public function testFormDefinitionRoundTrip(): void
    {
        [$fields, $errors] = FormDefinition::parse("Nom | text | 1\nVille | select | 0 | Abidjan, Bouaké");
        $this->assertSame([], $errors);
        $this->assertSame($fields, FormDefinition::parse(FormDefinition::toText($fields))[0]);
        [$data, $errs] = FormDefinition::validate($fields, ['f0' => 'Awa', 'f1' => 'Bouaké']);
        $this->assertSame(['Nom' => 'Awa', 'Ville' => 'Bouaké'], $data);
        $this->assertSame([], $errs);
    }

    public function testMailerNeutralisesHeaderInjectionAndRefusesBadRecipients(): void
    {
        $m = new Mailer();
        $this->assertFalse($m->send("evil@x.test\r\nBcc: spy@x.test", 'Sujet', 'corps'));
        $this->assertTrue($m->send('ok@x.test', "Salut\r\nBcc: spy@x.test", 'corps'));
        $log = $this->mailLog();
        $this->assertStringContainsString('SUBJECT:Salut Bcc: spy@x.test', $log);
        $this->assertStringNotContainsString("\nBcc:", $log);
    }

    public function testReservedSlugsCoverCrmRoutes(): void
    {
        $this->asAdmin('alpha.test', 'alpha');
        foreach (['contact', 'demandes', 'f'] as $slug) {
            $this->assertSame(422, $this->call('POST', '/admin/pages', 'alpha.test', ['title' => 'x', 'slug' => $slug, 'status' => 'published'])->status, $slug);
        }
    }
}
