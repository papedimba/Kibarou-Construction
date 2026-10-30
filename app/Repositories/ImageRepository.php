<?php
declare(strict_types=1);

namespace App\Repositories;

/** Ordered image gallery of an owner row (land or project), backed by the media library. */
abstract class ImageRepository extends TenantRepository
{
    protected string $fk;

    public function forOwner(int $ownerId): array
    {
        return $this->db->all(
            "SELECT i.id, i.media_id, m.filename, m.alt, m.mime FROM `{$this->table}` i JOIN media m ON m.site_id = i.site_id AND m.id = i.media_id
             WHERE i.site_id = ? AND i.`{$this->fk}` = ? ORDER BY i.position, i.id",
            [$this->tenant->id(), $ownerId]
        );
    }

    public function attach(int $ownerId, int $mediaId): int
    {
        $pos = (int) $this->db->value("SELECT COALESCE(MAX(position), 0) + 1 FROM `{$this->table}` WHERE site_id = ? AND `{$this->fk}` = ?", [$this->tenant->id(), $ownerId]);
        return $this->insert([$this->fk => $ownerId, 'media_id' => $mediaId, 'position' => $pos]);
    }

    /** @param list<int> $ownerIds @return array<int,string> owner id => first image filename */
    public function covers(array $ownerIds): array
    {
        if (!$ownerIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ownerIds), '?'));
        $rows = $this->db->all(
            "SELECT i.`{$this->fk}` AS owner, m.filename FROM `{$this->table}` i JOIN media m ON m.site_id = i.site_id AND m.id = i.media_id
             WHERE i.site_id = ? AND i.`{$this->fk}` IN ($in) ORDER BY i.position DESC, i.id DESC",
            [$this->tenant->id(), ...array_values($ownerIds)]
        );
        $out = [];
        foreach ($rows as $r) { // DESC order: the last write wins, i.e. the first image
            $out[(int) $r['owner']] = $r['filename'];
        }
        return $out;
    }
}
