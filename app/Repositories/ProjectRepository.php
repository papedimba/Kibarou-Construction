<?php
declare(strict_types=1);

namespace App\Repositories;

final class ProjectRepository extends CatalogRepository
{
    public const STATUSES = ['planned' => 'Planifié', 'in_progress' => 'En cours', 'completed' => 'Terminé'];
    protected string $table = 'projects';
    protected array $columns = ['name', 'slug', 'category', 'description', 'location_text', 'completed_on', 'status', 'is_published'];

    protected function flag(): string
    {
        return 'is_published';
    }
}
