<?php
declare(strict_types=1);

namespace App\Multisite;

use App\Core\Database;

final class TenantProvisioner
{
    private const DEFAULT_LAND_CATEGORIES = ['Résidentiel', 'Commercial', 'Industriel', 'Agricole', 'Urbain', 'Périurbain', 'Lotissement', 'Terrain nu', 'Autres'];

    public function __construct(private Database $db)
    {
    }

    /**
     * Irreversible removal of a site that is already marked "deleted" (two steps on purpose). Deletes every row carrying
     * its site_id, then its uploaded and private files. Users are kept (they may belong to other sites).
     */
    public function purge(int $siteId, string $root): void
    {
        if ($this->db->value('SELECT status FROM sites WHERE id = ?', [$siteId]) !== 'deleted') {
            throw new \DomainException('Seul un site déjà supprimé (statut « supprimé ») peut être purgé.');
        }
        $tables = array_column($this->db->all("SELECT DISTINCT table_name AS t FROM information_schema.columns WHERE table_schema = DATABASE() AND column_name = 'site_id' AND table_name <> 'sites'"), 't');
        $pdo = $this->db->pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0'); // the composite FKs forbid a plain cascade order; we delete explicitly instead
        try {
            $this->db->transaction(function (Database $db) use ($tables, $siteId) {
                foreach ($tables as $t) {
                    $db->run("DELETE FROM `$t` WHERE site_id = ?", [$siteId]);
                }
                $db->run('DELETE FROM sites WHERE id = ?', [$siteId]);
            });
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
        foreach (["public/uploads/$siteId", "storage/private/$siteId"] as $rel) {
            if (is_dir("$root/$rel")) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$root/$rel", \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
                    $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
                }
                rmdir("$root/$rel");
            }
        }
    }

    /** Creates site + primary domain + site admin (user is reused if the email already exists). Atomic. */
    public function create(string $name, string $slug, string $host, string $adminEmail, string $adminName, string $password): int
    {
        if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $slug)) {
            throw new \InvalidArgumentException('Identifiant (slug) invalide');
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email administrateur invalide');
        }
        if (trim($name) === '' || mb_strlen($password) < 12) {
            throw new \InvalidArgumentException('Nom requis et mot de passe de 12 caractères minimum');
        }
        return $this->db->transaction(function (Database $db) use ($name, $slug, $host, $adminEmail, $adminName, $password) {
            $siteId = $db->insert('sites', ['slug' => $slug, 'name' => $name]);
            (new DomainManager($db))->attach($siteId, $host, true);
            $userId = $db->value('SELECT id FROM users WHERE email = ?', [$adminEmail])
                ?? $db->insert('users', ['email' => $adminEmail, 'name' => $adminName, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            $roleId = $db->value("SELECT id FROM roles WHERE slug = 'site_admin'");
            $db->insert('site_members', ['site_id' => $siteId, 'user_id' => $userId, 'role_id' => $roleId]);
            foreach (self::DEFAULT_LAND_CATEGORIES as $cat) {
                $db->insert('land_categories', ['site_id' => $siteId, 'name' => $cat, 'slug' => Slug::make($cat)]);
            }
            return $siteId;
        });
    }
}
