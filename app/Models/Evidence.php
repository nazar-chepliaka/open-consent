<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Evidence extends Model
{
    use UsesUuid;

    protected $table = 'evidence';

    protected $fillable = [
        'vault_id',
        'type',
        'stored_object_id',
        'source_party_id',
        'description',
        'captured_at',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return ['captured_at' => 'datetime', 'recorded_at' => 'datetime'];
    }

    public function storedObject(): BelongsTo
    {
        return $this->belongsTo(StoredObject::class);
    }

    public function consentEvents(): BelongsToMany
    {
        return $this->belongsToMany(ConsentEvent::class, 'consent_event_evidence')->withTimestamps();
    }
}
