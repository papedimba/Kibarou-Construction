<?php
declare(strict_types=1);

namespace App\Repositories;

final class MediaRepository extends TenantRepository
{
    protected string $table = 'media';
    protected array $columns = ['filename', 'original_name', 'mime', 'size', 'alt'];

    public function all(): array
    {
        return $this->db->all('SELECT * FROM media WHERE site_id = ? ORDER BY id DESC', [$this->tenant->id()]);
    }
}
