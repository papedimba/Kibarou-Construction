<?php
declare(strict_types=1);

namespace App\Repositories;

final class ProjectImageRepository extends ImageRepository
{
    protected string $table = 'project_images';
    protected string $fk = 'project_id';
    protected array $columns = ['project_id', 'media_id', 'position'];
}
