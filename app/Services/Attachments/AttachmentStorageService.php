<?php

declare(strict_types=1);

namespace App\Services\Attachments;

use App\Models\Attachments\AttachmentModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;

final class AttachmentStorageService
{
    private const MAX_FILE_SIZE = 8388608;

    /**
     * @var array<string, string>
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private readonly ?AttachmentModel $attachmentModel = null)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function storeExpenseReceipt(?UploadedFile $file, int $householdId, int $uploadedBy, ?int $expenseId = null): ?array
    {
        return $this->storeEntityAttachment($file, $householdId, $uploadedBy, 'expense', $expenseId, 'expenses');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function storeSettlementAttachment(?UploadedFile $file, int $householdId, int $uploadedBy, ?int $settlementId = null): ?array
    {
        return $this->storeEntityAttachment($file, $householdId, $uploadedBy, 'settlement', $settlementId, 'settlements');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function storePinboardPostAttachment(?UploadedFile $file, int $householdId, int $uploadedBy, ?int $postId = null): ?array
    {
        return $this->storeEntityAttachment($file, $householdId, $uploadedBy, 'pinboard_post', $postId, 'pinboard');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storeEntityAttachment(
        ?UploadedFile $file,
        int $householdId,
        int $uploadedBy,
        string $entityType,
        ?int $entityId,
        string $directorySegment,
    ): ?array
    {
        if ($file === null || ! $file->isValid() || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file->hasMoved()) {
            throw new DomainException('Il file caricato non e piu disponibile per il salvataggio.');
        }

        $mimeType = $file->getMimeType();
        $clientExtension = strtolower((string) $file->getClientExtension());

        if (! is_string($mimeType) || ! array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new DomainException('Il formato della ricevuta non e supportato. Usa JPG, PNG, WEBP o PDF.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new DomainException('La ricevuta supera il limite massimo di 8 MB.');
        }

        if ($file->getSize() <= 0) {
            throw new DomainException('Il file caricato e vuoto o non valido.');
        }

        $now = new \DateTimeImmutable('now');
        $extension = self::ALLOWED_MIME_TYPES[$mimeType];

        if ($clientExtension !== '' && $clientExtension !== $extension) {
            throw new DomainException('L\'estensione del file non corrisponde al formato rilevato.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $relativePath = sprintf(
            'households/%d/%s/%s/%s/%s',
            $householdId,
            $directorySegment,
            $now->format('Y'),
            $now->format('m'),
            $storedName,
        );

        $targetDirectory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . dirname($relativePath);

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new DomainException('Impossibile preparare la directory per la ricevuta.');
        }

        $file->move($targetDirectory, basename($relativePath), true);
        $absolutePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $savedFile = new File($absolutePath, true);

        $attachmentId = ($this->attachmentModel ?? new AttachmentModel())->insert([
            'household_id' => $householdId,
            'uploaded_by' => $uploadedBy,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'original_name' => $this->sanitizeOriginalName($file->getClientName()),
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $savedFile->getSize(),
            'disk' => 'local',
            'path' => $relativePath,
            'checksum_sha256' => hash_file('sha256', $absolutePath),
        ], true);

        return ($this->attachmentModel ?? new AttachmentModel())->find((int) $attachmentId);
    }

    public function bindToExpense(int $attachmentId, int $expenseId): void
    {
        ($this->attachmentModel ?? new AttachmentModel())->update($attachmentId, [
            'entity_type' => 'expense',
            'entity_id' => $expenseId,
        ]);
    }

    public function bindToSettlement(int $attachmentId, int $settlementId): void
    {
        ($this->attachmentModel ?? new AttachmentModel())->update($attachmentId, [
            'entity_type' => 'settlement',
            'entity_id' => $settlementId,
        ]);
    }

    public function bindToPinboardPost(int $attachmentId, int $postId): void
    {
        ($this->attachmentModel ?? new AttachmentModel())->update($attachmentId, [
            'entity_type' => 'pinboard_post',
            'entity_id' => $postId,
        ]);
    }

    public function softDelete(int $attachmentId): void
    {
        ($this->attachmentModel ?? new AttachmentModel())->delete($attachmentId);
    }

    public function absolutePath(array $attachment): string
    {
        return WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $attachment['path']);
    }

    private function sanitizeOriginalName(string $originalName): string
    {
        $originalName = basename(trim($originalName));
        $originalName = preg_replace('/[^A-Za-z0-9._ -]/', '-', $originalName) ?? 'attachment';
        $originalName = trim($originalName, '. -');

        if ($originalName === '') {
            return 'attachment';
        }

        return substr($originalName, 0, 180);
    }
}
