<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalProvision extends Model
{
    use UsesUuid;

    protected $fillable = ['instrument_id', 'document_section_id', 'provision_key'];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(LegalInstrument::class, 'instrument_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(DocumentSection::class, 'document_section_id');
    }
}
