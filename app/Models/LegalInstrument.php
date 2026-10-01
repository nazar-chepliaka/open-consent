<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalInstrument extends Model
{
    use UsesUuid;

    protected $fillable = ['document_id', 'jurisdiction_id', 'instrument_type', 'official_number'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(LegalProvision::class, 'instrument_id');
    }
}
