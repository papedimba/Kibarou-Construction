<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\{DbTestCase, HttpHelpers};

final class UsersTest extends DbTestCase
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

    private function member(string $email, int $site): ?array
    {
        return $this->db->one('SELECT m.*, r.slug FROM site_members m JOIN users u ON u.id = m.user_id JOIN roles r ON r.id = m.role_id WHERE u.email = ? AND m.site_id = ?', [$email, $site]);
    }

    private function add(array $over = [], string $host = 'alpha.test')
    {
        return $this->call('POST', '/admin/utilisateurs', $host, $over + ['email' => 'new@alpha.test', 'name' => 'Nouveau', 'password' => 'a-long-initial-pass', 'role' => 'sales_manager']);
    }

    public function testOnlySiteAdminsCanOpenTheUsersScreen(): void
    {
        $this->assertSame(200, $this->call('GET', '/admin/utilisateurs', 'alpha.test')->status);
        $this->assertStringContainsString('Administrateur de site', $this->call('GET', '/admin/utilisateurs', 'alpha.test')->body);
        foreach (['editor', 'land_manager', 'sales_manager', 'btp_manager'] as $role) {
            $uid = $this->db->insert('users', ['email' => "$role@alpha.test", 'name' => $role, 'password_hash' => password_hash('correct-horse-battery', PASSWORD_DEFAULT)]);
            $this->db->insert('site_members', ['site_id' => $this->a, 'user_id' => $uid, 'role_id' => $this->db->value('SELECT id FROM roles WHERE slug = ?', [$role])]);
            $_SESSION = [];
            $this->login('alpha.test', "$role@alpha.test");
            $this->assertSame(403, $this->call('GET', '/admin/utilisateurs', 'alpha.test')->status, $role);
            $this->assertSame(403, $this->add(['email' => "x$role@alpha.test"])->status, "$role add");
            $this->assertNull($this->member("x$role@alpha.test", $this->a));
        }
    }

    public function testNewUserCanLogInOnThisSiteOnly(): void
    {
        $this->assertSame(302, $this->add()->status);
        $u = $this->db->one("SELECT * FROM users WHERE email = 'new@alpha.test'");
        $this->assertTrue(password_verify('a-long-initial-pass', $u['password_hash']));
        $this->assertSame(0, (int) $u['is_super_admin']);
        $this->assertSame('sales_manager', $this->member('new@alpha.test', $this->a)['slug']);
        $_SESSION = [];
        $this->assertSame(302, $this->login('alpha.test', 'new@alpha.test', 'a-long-initial-pass')->status);
        $this->assertSame(200, $this->call('GET', '/admin/prospects', 'alpha.test')->status);
        $_SESSION = [];
        $this->assertSame(401, $this->login('beta.test', 'new@alpha.test', 'a-long-initial-pass')->status);
    }

    public function testAttachingAnExistingAccountNeverTouchesItsPasswordOrName(): void
    {
        $before = $this->db->one("SELECT * FROM users WHERE email = 'admin@beta.test'");
        $this->add(['email' => 'admin@beta.test', 'name' => 'Hijack', 'password' => 'attacker-chosen-password', 'role' => 'editor']);
        $after = $this->db->one("SELECT * FROM users WHERE email = 'admin@beta.test'");
        $this->assertSame($before['password_hash'], $after['password_hash']);
        $this->assertSame($before['name'], $after['name']);
        $this->assertSame('editor', $this->member('admin@beta.test', $this->a)['slug']);
        $this->assertSame('site_admin', $this->member('admin@beta.test', $this->b)['slug']); // their own site untouched
        $this->assertNotNull($this->member('admin@beta.test', $this->a)); // and they cannot be taken over afterwards:
        $m = $this->member('admin@beta.test', $this->a);
        $this->call('POST', "/admin/utilisateurs/{$m['id']}/mot-de-passe", 'alpha.test', ['password' => 'attacker-chosen-password']);
        $this->assertSame($before['password_hash'], $this->db->value("SELECT password_hash FROM users WHERE email = 'admin@beta.test'"));
    }

    public function testInvalidInputIsRejected(): void
    {
        foreach ([['email' => 'nope'], ['role' => 'super_admin'], ['role' => 'x'], ['role' => ''], ['password' => 'short'], ['name' => '']] as $bad) {
            $this->add($bad);
        }
        $this->assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM users WHERE email = 'new@alpha.test'"));
        $this->add();
        $this->add(); // duplicate membership: refused, not a 500
        $this->assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM site_members m JOIN users u ON u.id = m.user_id WHERE u.email = 'new@alpha.test'"));
        $this->assertSame(0, (int) $this->db->value('SELECT SUM(is_super_admin) FROM users'));
    }

    public function testRoleChangeRemoveAndTheLastAdminGuard(): void
    {
        $admin = $this->member('admin@alpha.test', $this->a);
        $this->call('POST', "/admin/utilisateurs/{$admin['id']}/role", 'alpha.test', ['role' => 'editor']);
        $this->assertSame('site_admin', $this->member('admin@alpha.test', $this->a)['slug'], 'last admin demoted');
        $this->call('POST', "/admin/utilisateurs/{$admin['id']}/retirer", 'alpha.test');
        $this->assertNotNull($this->member('admin@alpha.test', $this->a), 'last admin removed');

        $this->add(['email' => 'second@alpha.test', 'role' => 'site_admin']);
        $second = $this->member('second@alpha.test', $this->a);
        $this->call('POST', "/admin/utilisateurs/{$second['id']}/role", 'alpha.test', ['role' => 'btp_manager']);
        $this->assertSame('btp_manager', $this->member('second@alpha.test', $this->a)['slug']);
        $this->call('POST', "/admin/utilisateurs/{$second['id']}/role", 'alpha.test', ['role' => 'site_admin']);
        $this->call('POST', "/admin/utilisateurs/{$admin['id']}/role", 'alpha.test', ['role' => 'editor']); // now allowed: another admin exists
        $this->assertSame('editor', $this->member('admin@alpha.test', $this->a)['slug']);
        $_SESSION = [];
        $this->login('alpha.test', 'second@alpha.test', 'a-long-initial-pass');
        $this->call('POST', "/admin/utilisateurs/{$admin['id']}/retirer", 'alpha.test');
        $this->assertNull($this->member('admin@alpha.test', $this->a));
        $this->assertNotNull($this->db->one("SELECT id FROM users WHERE email = 'admin@alpha.test'")); // account kept
        $this->call('POST', "/admin/utilisateurs/{$second['id']}/role", 'alpha.test', ['role' => 'nonexistent']);
        $this->assertSame('site_admin', $this->member('second@alpha.test', $this->a)['slug']);
    }

    public function testAnotherSitesMembersAreNotFound(): void
    {
        $theirs = $this->member('admin@beta.test', $this->b);
        foreach (['role' => ['role' => 'editor'], 'retirer' => [], 'mot-de-passe' => ['password' => 'a-very-long-new-pass']] as $action => $post) {
            $this->assertSame(404, $this->call('POST', "/admin/utilisateurs/{$theirs['id']}/$action", 'alpha.test', $post)->status, $action);
        }
        $this->assertSame('site_admin', $this->member('admin@beta.test', $this->b)['slug']);
        $this->assertTrue(password_verify('correct-horse-battery', (string) $this->db->value("SELECT password_hash FROM users WHERE email = 'admin@beta.test'")));
        $this->assertStringNotContainsString('admin@beta.test', $this->call('GET', '/admin/utilisateurs', 'alpha.test')->body);
    }

    public function testPasswordResetOnlyForSingleSiteAccountsAndClearsLockout(): void
    {
        $this->add();
        $m = $this->member('new@alpha.test', $this->a);
        $this->call('POST', "/admin/utilisateurs/{$m['id']}/mot-de-passe", 'alpha.test', ['password' => 'short']);
        $this->assertTrue(password_verify('a-long-initial-pass', (string) $this->db->value("SELECT password_hash FROM users WHERE email = 'new@alpha.test'")));
        $this->db->insert('login_attempts', ['email' => 'new@alpha.test', 'ip' => '1.1.1.1']);
        $this->call('POST', "/admin/utilisateurs/{$m['id']}/mot-de-passe", 'alpha.test', ['password' => 'brand-new-long-pass']);
        $this->assertTrue(password_verify('brand-new-long-pass', (string) $this->db->value("SELECT password_hash FROM users WHERE email = 'new@alpha.test'")));
        $this->assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM login_attempts WHERE email = 'new@alpha.test'"));
        // once the account also belongs to another site, this site loses the right to reset it
        $this->login('beta.test', 'admin@beta.test');
        $this->add(['email' => 'new@alpha.test', 'role' => 'editor'], 'beta.test');
        $_SESSION = [];
        $this->login('alpha.test', 'admin@alpha.test');
        $before = $this->db->value("SELECT password_hash FROM users WHERE email = 'new@alpha.test'");
        $this->call('POST', "/admin/utilisateurs/{$m['id']}/mot-de-passe", 'alpha.test', ['password' => 'takeover-attempt-pass']);
        $this->assertSame($before, $this->db->value("SELECT password_hash FROM users WHERE email = 'new@alpha.test'"));
    }

    public function testStateChangingActionsNeedCsrf(): void
    {
        $this->assertSame(419, $this->call('POST', '/admin/utilisateurs', 'alpha.test', ['email' => 'x@alpha.test', 'role' => 'editor'], false)->status);
    }
}
