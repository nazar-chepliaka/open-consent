<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use UsesUuid;

    protected $fillable = ['owner_vault_id', 'title', 'visibility'];

    public function ownerVault(): BelongsTo
    {
        return $this->belongsTo(Vault::class, 'owner_vault_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public' && $this->owner_vault_id === null;
    }
}
