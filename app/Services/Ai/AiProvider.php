<?php

namespace App\Services\Ai;

use App\Models\AiConnection;

interface AiProvider
{
    /**
     * @return array{
     *     displayName: string,
     *     credentialsHelpUrl?: string|null,
     *     credentialsHelpLabel?: string|null
     * }
     */
    public function metadata(): array;

    public function testConnection(AiConnection $connection): AiProviderResult;

    /**
     * @return list<string>
     */
    public function models(AiConnection $connection): array;
}
