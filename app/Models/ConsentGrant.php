<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentGrant extends Model
{
    use UsesUuid;

    protected $fillable = [
        'vault_id',
        'relationship_id',
        'consent_type',
        'grantor_party_id',
        'data_subject_party_id',
        'recipient_party_id',
        'document_version_id',
        'purpose',
        'scope',
        'granted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return ['scope' => 'array', 'granted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ConsentEvent::class);
    }
}
