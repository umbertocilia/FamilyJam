<?php

declare(strict_types=1);

namespace App\Services\Media;

use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use Throwable;

final class AvatarImageService
{
    private const MAX_BYTES = 4_194_304;
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME_PREFIX = 'image/';
    private const OUTPUT_QUALITY = 78;
    private const OUTPUT_SIZE = 320;

    public function storeUserAvatar(?UploadedFile $file, int $userId, ?string $currentPath = null): ?string
    {
        return $this->store($file, 'users/' . $userId, 'user-' . $userId, $currentPath);
    }

    public function storeHouseholdAvatar(?UploadedFile $file, int $householdId, ?string $currentPath = null): ?string
    {
        return $this->store($file, 'households/' . $householdId, 'household-' . $householdId, $currentPath);
    }

    public function deleteManagedAvatar(?string $path): void
    {
        $this->deleteManagedFile($path);
    }

    private function store(?UploadedFile $file, string $relativeDirectory, string $prefix, ?string $currentPath): ?string
    {
        if ($file === null || $file->getClientName() === '') {
            return $currentPath;
        }

        if (! $file->isValid()) {
            throw new DomainException('The uploaded avatar is not valid.');
        }

        if (($file->getSize() ?? 0) > self::MAX_BYTES) {
            throw new DomainException('Avatar images must stay under 4 MB.');
        }

        $extension = strtolower((string) $file->getClientExtension());
        $mimeType = strtolower((string) $file->getMimeType());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true) || ! str_starts_with($mimeType, self::ALLOWED_MIME_PREFIX)) {
            throw new DomainException('Avatar images must be JPG, PNG or WEBP.');
        }

        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
        $targetDirectory = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new DomainException('Unable to prepare the avatar directory.');
        }

        $targetFilename = $prefix . '-' . date('YmdHis') . '.jpg';
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $targetFilename;

        try {
            service('image')
                ->withFile($file->getTempName())
                ->fit(self::OUTPUT_SIZE, self::OUTPUT_SIZE, 'center')
                ->save($targetPath, self::OUTPUT_QUALITY);
        } catch (Throwable $exception) {
            throw new DomainException('Unable to process the avatar image.', 0, $exception);
        }

        $this->deleteManagedFile($currentPath);

        return 'uploads/avatars/' . $relativeDirectory . '/' . $targetFilename;
    }

    private function deleteManagedFile(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (! str_starts_with($normalized, 'uploads/avatars/')) {
            return;
        }

        $absolute = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);

        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
