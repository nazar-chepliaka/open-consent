<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentVersion extends Model
{
    use UsesUuid;

    protected $fillable = [
        'document_id',
        'original_object_id',
        'version_label',
        'published_at',
        'effective_from',
        'effective_to',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function originalObject(): BelongsTo
    {
        return $this->belongsTo(StoredObject::class, 'original_object_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(DocumentSection::class);
    }
}
