<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vault extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = ['owner_id', 'name'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vault_members')->withPivot('role')->withTimestamps();
    }

    public function archiveEntries(): HasMany
    {
        return $this->hasMany(ArchiveEntry::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_vault_id');
    }

    public function storedObjects(): HasMany
    {
        return $this->hasMany(StoredObject::class);
    }
}
