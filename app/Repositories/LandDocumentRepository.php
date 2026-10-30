<?php
declare(strict_types=1);

namespace App\Repositories;

final class LandDocumentRepository extends TenantRepository
{
    public const TYPES = ['acd' => 'ACD', 'titre_foncier' => 'Titre foncier', 'attestation' => 'Attestation', 'lotissement' => 'Document de lotissement', 'plan' => 'Plan', 'autre' => 'Autre justificatif'];
    protected string $table = 'land_documents';
    protected array $columns = ['land_id', 'type', 'doc_date', 'verification', 'visibility', 'filename', 'original_name', 'mime', 'size'];

    public function forLand(int $landId, bool $publicOnly = false): array
    {
        return $this->db->all(
            'SELECT * FROM land_documents WHERE site_id = ? AND land_id = ?' . ($publicOnly ? " AND visibility = 'public'" : '') . ' ORDER BY id',
            [$this->tenant->id(), $landId]
        );
    }
}
