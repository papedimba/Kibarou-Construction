<?php
declare(strict_types=1);

namespace App\Repositories;

final class SettingsRepository extends TenantRepository
{
    public const THEMES = ['corporate-premium' => 'Corporate Premium', 'minimaliste-moderne' => 'Minimaliste Moderne', 'elegance-prestige' => 'Élégance & Prestige'];
    public const KEYS = ['tagline', 'phone', 'email', 'whatsapp', 'address', 'logo_url', 'theme', 'meta_description', 'home_page', 'stage_1', 'stage_2', 'stage_3'];
    protected string $table = 'settings';
    protected array $columns = [];

    /** @return array<string,string> */
    public function all(): array
    {
        return array_column($this->db->all('SELECT `key`, value FROM settings WHERE site_id = ?', [$this->tenant->id()]), 'value', 'key');
    }

    public function set(string $key, string $value): void
    {
        if (!in_array($key, self::KEYS, true)) {
            throw new \InvalidArgumentException("Unknown setting $key");
        }
        $this->db->run('INSERT INTO settings (site_id, `key`, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$this->tenant->id(), $key, $value]);
    }
}
