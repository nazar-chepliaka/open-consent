<?php

namespace App\Services\Ai;

use App\Models\AiConnection;
use InvalidArgumentException;

class AiManager
{
    public function __construct(
        private readonly OpenAiProvider $openAiProvider,
    ) {}

    public function provider(AiConnection $connection): AiProvider
    {
        return $this->providerByKey($connection->provider);
    }

    public function providerByKey(string $provider): AiProvider
    {
        return match ($provider) {
            AiConnection::PROVIDER_OPENAI => $this->openAiProvider,
            default => throw new InvalidArgumentException('Unsupported AI provider.'),
        };
    }

    /**
     * @return array<string, array{
     *     displayName: string,
     *     credentialsHelpUrl?: string|null,
     *     credentialsHelpLabel?: string|null
     * }>
     */
    public function providerMetadata(): array
    {
        return collect($this->supportedProviders())
            ->mapWithKeys(fn (string $provider) => [$provider => $this->providerByKey($provider)->metadata()])
            ->all();
    }

    /**
     * @return list<string>
     */
    public function supportedProviders(): array
    {
        return [
            AiConnection::PROVIDER_OPENAI,
        ];
    }

    public function testConnection(AiConnection $connection): AiProviderResult
    {
        return $this->provider($connection)->testConnection($connection);
    }

    /**
     * @return list<string>
     */
    public function models(AiConnection $connection): array
    {
        return $this->provider($connection)->models($connection);
    }
}
