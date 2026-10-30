<?php
declare(strict_types=1);

namespace App\Repositories;

final class ServiceRepository extends CatalogRepository
{
    public const CATEGORIES = [
        'construction-maisons' => 'Construction de maisons', 'construction-batiments' => 'Construction de bâtiments', 'gros-oeuvre' => 'Gros œuvre',
        'second-oeuvre' => 'Second œuvre', 'travaux-publics' => 'Travaux publics', 'genie-civil' => 'Génie civil', 'terrassement' => 'Terrassement',
        'renovation' => 'Rénovation', 'rehabilitation' => 'Réhabilitation', 'amenagement' => 'Aménagement', 'etudes-techniques' => 'Études techniques', 'autres' => 'Autres prestations',
    ];
    protected string $table = 'construction_services';
    protected array $columns = ['media_id', 'category', 'title', 'slug', 'description', 'is_active'];

    protected function flag(): string
    {
        return 'is_active';
    }
}
