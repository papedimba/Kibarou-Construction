<?php
declare(strict_types=1);

namespace App\Repositories;

final class PlotRepository extends TenantRepository
{
    protected string $table = 'land_plots';
    protected array $columns = ['development_id', 'reference', 'number', 'area_m2', 'price_xof', 'status', 'location_note'];

    public function forDevelopment(int $devId): array
    {
        return $this->db->all('SELECT * FROM land_plots WHERE site_id = ? AND development_id = ? ORDER BY LENGTH(number), number', [$this->tenant->id(), $devId]);
    }
}
