<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Relationship extends Model
{
    use UsesUuid;

    protected $fillable = ['vault_id', 'type', 'status', 'started_at', 'ended_at', 'source_note'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'relationship_parties')->withPivot('role')->withTimestamps();
    }

    public function documentVersions(): BelongsToMany
    {
        return $this->belongsToMany(DocumentVersion::class, 'relationship_documents')->withPivot('role', 'applicable_from', 'applicable_to')->withTimestamps();
    }
}
