<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoredObject extends Model
{
    use UsesUuid;

    protected $fillable = [
        'vault_id',
        'storage_profile_id',
        'object_key',
        'size_bytes',
        'mime_type',
        'content_hash_algorithm',
        'content_hash',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function storageProfile(): BelongsTo
    {
        return $this->belongsTo(StorageProfile::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }
}
