<?php

namespace App\Services;

use App\Models\StorageProfile;
use App\Models\StoredObject;
use App\Models\Vault;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StoredObjectService
{
    public function storeUploadedFile(Vault $vault, UploadedFile $file, ?StorageProfile $profile = null): StoredObject
    {
        return $this->storeBytes(
            vault: $vault,
            bytes: $file->get(),
            mimeType: $file->getMimeType(),
            extension: $file->getClientOriginalExtension() ?: $file->extension(),
            profile: $profile,
        );
    }

    public function storeBytes(Vault $vault, string $bytes, ?string $mimeType = null, ?string $extension = null, ?StorageProfile $profile = null): StoredObject
    {
        if ($profile !== null && $profile->vault_id !== $vault->id) {
            throw new RuntimeException('Storage profile does not belong to the vault.');
        }

        $hash = hash('sha256', $bytes);
        $suffix = $extension ? '.'.ltrim($extension, '.') : '';
        $objectKey = sprintf('vaults/%s/objects/%s%s', $vault->id, (string) Str::uuid(), $suffix);

        Storage::disk('local')->put($objectKey, $bytes);

        return StoredObject::create([
            'vault_id' => $vault->id,
            'storage_profile_id' => $profile?->id,
            'object_key' => $objectKey,
            'size_bytes' => strlen($bytes),
            'mime_type' => $mimeType,
            'content_hash_algorithm' => 'sha256',
            'content_hash' => $hash,
        ]);
    }

    public function verifyIntegrity(StoredObject $object): bool
    {
        if (! Storage::disk('local')->exists($object->object_key)) {
            return false;
        }

        return hash('sha256', Storage::disk('local')->get($object->object_key)) === $object->content_hash;
    }

    public function moveWithinConfiguredStorage(StoredObject $object, ?StorageProfile $profile = null): StoredObject
    {
        if ($profile !== null && $profile->vault_id !== $object->vault_id) {
            throw new RuntimeException('Storage profile does not belong to the stored object vault.');
        }

        $extension = pathinfo($object->object_key, PATHINFO_EXTENSION);
        $newKey = sprintf(
            'vaults/%s/objects/%s%s',
            $object->vault_id,
            (string) Str::uuid(),
            $extension ? '.'.$extension : '',
        );

        Storage::disk('local')->copy($object->object_key, $newKey);
        Storage::disk('local')->delete($object->object_key);

        $object->forceFill([
            'storage_profile_id' => $profile?->id,
            'object_key' => $newKey,
        ])->save();

        return $object->refresh();
    }
}
