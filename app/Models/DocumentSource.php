<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSource extends Model
{
    use UsesUuid;

    protected $fillable = ['document_version_id', 'source_url', 'publisher', 'retrieved_at', 'verification_status'];

    protected function casts(): array
    {
        return ['retrieved_at' => 'datetime'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }
}
