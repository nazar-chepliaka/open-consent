<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConnection extends Model
{
    use UsesUuid;

    public const PROVIDER_OPENAI = 'openai';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'configuration',
        'credentials',
        'is_enabled',
        'last_tested_at',
        'last_test_status',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function selectedModel(): ?string
    {
        $configuration = $this->configuration ?? [];
        $model = $configuration['model'] ?? null;

        return is_string($model) && $model !== '' ? $model : null;
    }

    public function hasCredentials(): bool
    {
        return filled($this->credentials['api_key'] ?? null);
    }
}
