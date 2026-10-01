<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConsentEvent extends Model
{
    use UsesUuid;

    protected $fillable = [
        'vault_id',
        'consent_grant_id',
        'event_type',
        'actor_party_id',
        'occurred_at',
        'recorded_at',
        'source_type',
        'description',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'recorded_at' => 'datetime'];
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(ConsentGrant::class, 'consent_grant_id');
    }

    public function evidence(): BelongsToMany
    {
        return $this->belongsToMany(Evidence::class, 'consent_event_evidence')->withTimestamps();
    }
}
