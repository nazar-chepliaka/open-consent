<?php

namespace App\Services\Ai;

use App\Models\AiConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;
use UnexpectedValueException;

class OpenAiProvider implements AiProvider
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function metadata(): array
    {
        return [
            'displayName' => 'OpenAI',
            'credentialsHelpUrl' => 'https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key',
            'credentialsHelpLabel' => 'Інструкція з отримання API-ключа OpenAI',
        ];
    }

    public function testConnection(AiConnection $connection): AiProviderResult
    {
        if (! $connection->hasCredentials()) {
            return AiProviderResult::failure('API key не налаштовано.');
        }

        try {
            $this->models($connection);

            return AiProviderResult::success();
        } catch (RequestException $exception) {
            return AiProviderResult::failure($this->messageForStatus($exception->response->status()));
        } catch (ConnectionException) {
            return AiProviderResult::failure('Не вдалося зʼєднатися з провайдером ШІ.');
        } catch (Throwable) {
            return AiProviderResult::failure('Перевірка не вдалася. Перевірте налаштування та спробуйте ще раз.');
        }
    }

    public function models(AiConnection $connection): array
    {
        $apiKey = $connection->credentials['api_key'] ?? null;

        if (! is_string($apiKey) || $apiKey === '') {
            throw new UnexpectedValueException('Missing OpenAI API key.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(15)
            ->get(self::BASE_URL.'/models')
            ->throw();

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new UnexpectedValueException('Malformed OpenAI models response.');
        }

        $models = collect($data)
            ->pluck('id')
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values($models);
    }

    private function messageForStatus(int $status): string
    {
        return match ($status) {
            401, 403 => 'Облікові дані не прийняті провайдером.',
            408, 429 => 'Провайдер тимчасово обмежив або затримав запит.',
            default => $status >= 500
                ? 'Провайдер тимчасово недоступний.'
                : 'Перевірка не вдалася. Перевірте налаштування та спробуйте ще раз.',
        };
    }
}
