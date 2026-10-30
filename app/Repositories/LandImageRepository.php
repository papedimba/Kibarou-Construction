<?php
declare(strict_types=1);

namespace App\Repositories;

final class LandImageRepository extends ImageRepository
{
    protected string $table = 'land_images';
    protected string $fk = 'land_id';
    protected array $columns = ['land_id', 'media_id', 'position'];
}
