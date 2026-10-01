<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Party extends Model
{
    use UsesUuid;

    protected $fillable = ['vault_id', 'type', 'display_name', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
