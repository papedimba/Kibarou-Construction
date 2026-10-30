<?php
declare(strict_types=1);

namespace App\Repositories;

final class LocationRepository extends TenantRepository
{
    public const TYPES = ['district' => 'District', 'region' => 'Région', 'departement' => 'Département', 'ville' => 'Ville', 'commune' => 'Commune', 'quartier' => 'Quartier'];
    protected string $table = 'locations';
    protected array $columns = ['parent_id', 'type', 'name'];

    /** Flat list with a "District › Région › Ville" label, ordered so children follow parents. */
    public function tree(): array
    {
        $rows = $this->db->all('SELECT * FROM locations WHERE site_id = ? ORDER BY name', [$this->tenant->id()]);
        $by = [];
        foreach ($rows as $r) {
            $by[(int) ($r['parent_id'] ?? 0)][] = $r;
        }
        $out = [];
        $walk = function (int $parent, string $prefix, int $depth) use (&$walk, &$out, $by) {
            foreach ($by[$parent] ?? [] as $r) {
                $label = $prefix . $r['name'];
                $out[] = $r + ['label' => $label, 'depth' => $depth];
                if ($depth < 8) { // guards against accidental cycles
                    $walk((int) $r['id'], $label . ' › ', $depth + 1);
                }
            }
        };
        $walk(0, '', 0);
        return $out;
    }

    public function label(?int $id): ?string
    {
        foreach ($id ? $this->tree() : [] as $r) {
            if ((int) $r['id'] === $id) {
                return $r['label'];
            }
        }
        return null;
    }

    /** @return list<int> the location and all its descendants */
    public function withDescendants(int $id): array
    {
        $rows = $this->db->all(
            'WITH RECURSIVE t(id) AS (SELECT id FROM locations WHERE site_id = ? AND id = ? UNION ALL SELECT l.id FROM locations l JOIN t ON l.parent_id = t.id WHERE l.site_id = ?) SELECT id FROM t',
            [$this->tenant->id(), $id, $this->tenant->id()]
        );
        return array_map('intval', array_column($rows, 'id'));
    }
}
