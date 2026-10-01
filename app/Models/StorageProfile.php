<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageProfile extends Model
{
    use UsesUuid;

    protected $fillable = ['vault_id', 'driver', 'name', 'configuration'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
