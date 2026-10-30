<?php
declare(strict_types=1);

namespace App\Multisite;

use App\Core\{Database, Env};

final class TenantResolver
{
    public function __construct(private Database $db)
    {
    }

    /** host (already normalized) -> site row, or null. Order: exact domain, www-less, <slug>.<base domain>. */
    public function resolve(string $host): ?array
    {
        if ($host === '') {
            return null;
        }
        $candidates = [$host];
        if (str_starts_with($host, 'www.')) {
            $candidates[] = substr($host, 4);
        }
        foreach ($candidates as $h) {
            $site = $this->db->one('SELECT s.* FROM domains d JOIN sites s ON s.id = d.site_id WHERE d.host = ?', [$h]);
            if ($site) {
                return $site;
            }
        }
        $base = Env::get('PLATFORM_BASE_DOMAIN');
        if ($base && str_ends_with($host, ".$base")) {
            $slug = substr($host, 0, -strlen(".$base"));
            if (preg_match('/^[a-z0-9-]+$/', $slug)) {
                return $this->db->one('SELECT * FROM sites WHERE slug = ?', [$slug]);
            }
        }
        return null;
    }
}
