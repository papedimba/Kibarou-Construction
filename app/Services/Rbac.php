<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class Rbac
{
    public function __construct(private Database $db)
    {
    }

    public function isMember(array $user, int $siteId): bool
    {
        return (bool) $user['is_super_admin']
            || (bool) $this->db->value('SELECT 1 FROM site_members WHERE user_id = ? AND site_id = ? LIMIT 1', [$user['id'], $siteId]);
    }

    /** Roles are per (user, site): the same user can be site_admin on A and editor on B. */
    public function can(array $user, int $siteId, string $permission): bool
    {
        if ($user['is_super_admin']) {
            return true;
        }
        return (bool) $this->db->value(
            'SELECT 1 FROM site_members m JOIN role_permissions rp ON rp.role_id = m.role_id JOIN permissions p ON p.id = rp.permission_id
             WHERE m.user_id = ? AND m.site_id = ? AND p.slug = ? LIMIT 1',
            [$user['id'], $siteId, $permission]
        );
    }
}
