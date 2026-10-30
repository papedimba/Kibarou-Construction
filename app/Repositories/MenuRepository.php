<?php
declare(strict_types=1);

namespace App\Repositories;

/** Menu items of the current site. The menu row itself is created on first use. */
final class MenuRepository extends TenantRepository
{
    public const LOCATIONS = ['header' => 'En-tête', 'footer' => 'Pied de page'];
    protected string $table = 'menu_items';
    protected array $columns = ['menu_id', 'label', 'url', 'position'];

    /** Only site-relative paths and http(s) URLs: blocks javascript:/data: links. */
    public static function validUrl(string $url): bool
    {
        return (bool) preg_match('#^(/(?!/)[^\s]*|https?://[^\s]+)$#', $url) && strlen($url) <= 500;
    }

    public function items(string $location): array
    {
        return $this->db->all(
            'SELECT i.* FROM menu_items i JOIN menus m ON m.site_id = i.site_id AND m.id = i.menu_id WHERE i.site_id = ? AND m.location = ? ORDER BY i.position, i.id',
            [$this->tenant->id(), $location]
        );
    }

    public function add(string $location, string $label, string $url): int
    {
        $sid = $this->tenant->id();
        $this->db->run('INSERT IGNORE INTO menus (site_id, location) VALUES (?, ?)', [$sid, $location]);
        $menuId = (int) $this->db->value('SELECT id FROM menus WHERE site_id = ? AND location = ?', [$sid, $location]);
        $pos = (int) $this->db->value('SELECT COALESCE(MAX(position), 0) + 1 FROM menu_items WHERE site_id = ? AND menu_id = ?', [$sid, $menuId]);
        return $this->insert(['menu_id' => $menuId, 'label' => $label, 'url' => $url, 'position' => $pos]);
    }
}
