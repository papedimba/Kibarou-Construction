<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Multisite\TenantContext;
use App\Repositories\LandDocumentRepository;

/** Land documents live in storage/private (outside the web root) and are only served through a controller. */
final class DocumentService
{
    private const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    private const MAX_BYTES = 10_000_000;

    public function __construct(private LandDocumentRepository $docs, private TenantContext $tenant)
    {
    }

    public function dir(): string
    {
        return dirname(__DIR__, 2) . '/storage/private/' . $this->tenant->id();
    }

    public function path(array $doc): string
    {
        return $this->dir() . '/' . basename($doc['filename']);
    }

    /** @return string|null error message, null on success */
    public function store(array $file, int $landId, string $type, string $date, string $visibility, bool $isUpload = true): ?string
    {
        if (!isset(LandDocumentRepository::TYPES[$type]) || !in_array($visibility, ['private', 'public'], true)) {
            return 'Type ou visibilité invalide.';
        }
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 'Date invalide.';
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || ($isUpload && !is_uploaded_file($file['tmp_name']))) {
            return 'Échec du téléversement.';
        }
        if (filesize($file['tmp_name']) > self::MAX_BYTES) {
            return 'Fichier trop volumineux (10 Mo max).';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime])) {
            return 'Format non autorisé (PDF, JPEG, PNG).';
        }
        $dir = $this->dir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return 'Dossier de stockage inaccessible.';
        }
        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!($isUpload ? move_uploaded_file($file['tmp_name'], "$dir/$name") : copy($file['tmp_name'], "$dir/$name"))) {
            return 'Échec de l\'enregistrement.';
        }
        $this->docs->insert([
            'land_id' => $landId, 'type' => $type, 'doc_date' => $date ?: null, 'visibility' => $visibility, 'verification' => 'unverified',
            'filename' => $name, 'original_name' => mb_substr(basename((string) ($file['name'] ?? $name)), 0, 190), 'mime' => $mime, 'size' => filesize("$dir/$name"),
        ]);
        return null;
    }

    public function delete(int $id): bool
    {
        $doc = $this->docs->find($id);
        if (!$doc) {
            return false;
        }
        $this->docs->delete($id);
        @unlink($this->path($doc));
        return true;
    }

    /** Served only through here, with the stored (not client-chosen) MIME type, as an attachment. */
    public function download(array $doc): Response
    {
        $file = $this->path($doc);
        if (!is_file($file)) {
            return new Response('Fichier introuvable.', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $ext = pathinfo($doc['filename'], PATHINFO_EXTENSION);
        return new Response((string) file_get_contents($file), 200, [
            'Content-Type' => $doc['mime'],
            'Content-Disposition' => 'attachment; filename="document-' . (int) $doc['id'] . '.' . $ext . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Remove the files of every document of a land (rows go with the land via ON DELETE CASCADE). */
    public function purgeLand(int $landId): void
    {
        foreach ($this->docs->forLand($landId) as $d) {
            @unlink($this->path($d));
        }
    }
}
