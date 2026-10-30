<?php
declare(strict_types=1);

namespace App\Services;

use App\Multisite\TenantContext;
use App\Repositories\MediaRepository;

final class MediaService
{
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    private const MAX_BYTES = 5_000_000;

    public function __construct(private MediaRepository $media, private TenantContext $tenant)
    {
    }

    public function dir(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/' . $this->tenant->id();
    }

    /** @param array $file one $_FILES entry. @return string|null error message, null on success */
    public function store(array $file, string $alt = '', bool $isUpload = true): ?string
    {
        return $this->storeImage($file, $alt, $isUpload)[1];
    }

    /** @return array{0:?int,1:?string} [media id, error message] */
    public function storeImage(array $file, string $alt = '', bool $isUpload = true): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)) {
            return [null, 'Échec du téléversement.'];
        }
        if ($isUpload && !is_uploaded_file($file['tmp_name'])) {
            return [null, 'Fichier invalide.'];
        }
        if (filesize($file['tmp_name']) > self::MAX_BYTES) {
            return [null, 'Fichier trop volumineux (5 Mo max).'];
        }
        // Type comes from the file's content, never from the client-supplied name or MIME.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return [null, 'Format non autorisé (JPEG, PNG, WebP, GIF).'];
        }
        $dir = $this->dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [null, 'Dossier de stockage inaccessible.'];
        }
        $name = bin2hex(random_bytes(12)) . '.' . self::TYPES[$mime];
        $moved = $isUpload ? move_uploaded_file($file['tmp_name'], "$dir/$name") : copy($file['tmp_name'], "$dir/$name");
        if (!$moved) {
            return [null, 'Échec de l\'enregistrement.'];
        }
        $id = $this->media->insert([
            'filename' => $name, 'original_name' => mb_substr(basename((string) ($file['name'] ?? $name)), 0, 190),
            'mime' => $mime, 'size' => filesize("$dir/$name"), 'alt' => mb_substr(trim($alt), 0, 190) ?: null,
        ]);
        return [$id, null];
    }

    public function delete(int $id): bool
    {
        $m = $this->media->find($id);
        if (!$m) {
            return false;
        }
        $this->media->delete($id);
        @unlink($this->dir() . '/' . basename($m['filename']));
        return true;
    }
}
