<?php
declare(strict_types=1);

namespace App\Multisite;

use App\Core\Database;

final class DomainManager
{
    public function __construct(private Database $db)
    {
    }

    /** Lowercase, strip port and trailing dot. Returns '' when the host is not a valid hostname. */
    public static function normalize(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host);
        $host = rtrim($host, '.');
        return preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/', $host) ? $host : '';
    }

    public function attach(int $siteId, string $host, bool $primary = false): int
    {
        $host = self::normalize($host);
        if ($host === '') {
            throw new \InvalidArgumentException('Nom de domaine invalide');
        }
        if ($this->db->value('SELECT 1 FROM domains WHERE host = ?', [$host])) {
            throw new \DomainException("Le domaine $host est déjà associé à un site");
        }
        return $this->db->insert('domains', ['site_id' => $siteId, 'host' => $host, 'is_primary' => (int) $primary]);
    }

    public function detach(int $siteId, int $domainId): void
    {
        $this->db->run('DELETE FROM domains WHERE id = ? AND site_id = ? AND is_primary = 0', [$domainId, $siteId]);
    }

    public function forSite(int $siteId): array
    {
        return $this->db->all('SELECT * FROM domains WHERE site_id = ? ORDER BY is_primary DESC, host', [$siteId]);
    }
}
