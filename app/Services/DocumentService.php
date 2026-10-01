<?php

namespace App\Services;

use App\Models\ArchiveEntry;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\StoredObject;
use App\Models\Vault;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentService
{
    public function __construct(private readonly StoredObjectService $objects) {}

    public function uploadPrivateDocument(Vault $vault, UploadedFile $file, array $attributes): DocumentVersion
    {
        return DB::transaction(function () use ($vault, $file, $attributes) {
            $object = $this->objects->storeUploadedFile($vault, $file);

            $document = Document::create([
                'owner_vault_id' => $vault->id,
                'type' => $attributes['type'] ?? 'private_document',
                'title' => $attributes['title'],
                'visibility' => 'private',
            ]);

            $version = $this->createVersion($document, $object, [
                'version_label' => $attributes['version_label'] ?? 'initial',
                'captured_at' => $attributes['captured_at'] ?? now(),
                'published_at' => $attributes['published_at'] ?? null,
                'effective_from' => $attributes['effective_from'] ?? null,
                'effective_to' => $attributes['effective_to'] ?? null,
            ]);

            ArchiveEntry::create([
                'vault_id' => $vault->id,
                'document_version_id' => $version->id,
                'title' => $attributes['archive_title'] ?? $document->title,
                'added_at' => now(),
            ]);

            return $version->load('document', 'originalObject');
        });
    }

    public function addPrivateVersion(Document $document, UploadedFile $file, array $attributes): DocumentVersion
    {
        if ($document->owner_vault_id === null || $document->visibility !== 'private') {
            throw new RuntimeException('Only private vault documents can receive uploaded private versions.');
        }

        return DB::transaction(function () use ($document, $file, $attributes) {
            $vault = Vault::findOrFail($document->owner_vault_id);
            $object = $this->objects->storeUploadedFile($vault, $file);
            $version = $this->createVersion($document, $object, $attributes);

            ArchiveEntry::create([
                'vault_id' => $vault->id,
                'document_version_id' => $version->id,
                'title' => $attributes['archive_title'] ?? $document->title,
                'added_at' => now(),
            ]);

            return $version->load('document', 'originalObject');
        });
    }

    public function attachPublicVersionToVault(Vault $vault, DocumentVersion $version, ?string $title = null): ArchiveEntry
    {
        $document = $version->document()->firstOrFail();

        if (! $document->isPublic()) {
            throw new RuntimeException('Only public document versions can be shared across vaults this way.');
        }

        return ArchiveEntry::firstOrCreate(
            ['vault_id' => $vault->id, 'document_version_id' => $version->id],
            ['title' => $title ?? $document->title, 'added_at' => now()],
        );
    }

    public function createVersion(Document $document, ?StoredObject $object, array $attributes): DocumentVersion
    {
        if ($object !== null && $document->owner_vault_id !== $object->vault_id) {
            throw new RuntimeException('Stored object does not belong to the document vault.');
        }

        return $document->versions()->create([
            'original_object_id' => $object?->id,
            'version_label' => $attributes['version_label'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
            'effective_from' => $attributes['effective_from'] ?? null,
            'effective_to' => $attributes['effective_to'] ?? null,
            'captured_at' => $attributes['captured_at'] ?? now(),
        ]);
    }
}
