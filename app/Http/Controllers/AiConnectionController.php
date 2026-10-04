<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiConnectionRequest;
use App\Http\Requests\UpdateAiConnectionRequest;
use App\Models\AiConnection;
use App\Services\Ai\AiManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AiConnectionController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.ai.index', [
            'connections' => $request->user()->aiConnections()->orderBy('name')->get(),
            'defaultConnectionId' => $request->user()->default_ai_connection_id,
        ]);
    }

    public function create(AiManager $ai): View
    {
        return view('settings.ai.create', [
            'providerMetadata' => $ai->providerMetadata(),
        ]);
    }

    public function store(StoreAiConnectionRequest $request): RedirectResponse
    {
        $connection = DB::transaction(function () use ($request): AiConnection {
            $connection = $request->user()->aiConnections()->create([
                'name' => $request->validated('name'),
                'provider' => $request->validated('provider'),
                'configuration' => $this->configurationFromRequest($request),
                'credentials' => ['api_key' => $request->validated('api_key')],
                'is_enabled' => $request->boolean('is_enabled', true),
            ]);

            if ($connection->is_enabled && $request->boolean('make_default')) {
                $request->user()->forceFill(['default_ai_connection_id' => $connection->id])->save();
            }

            return $connection;
        });

        return redirect()
            ->route('settings.ai.edit', $connection)
            ->with('status', 'Підключення ШІ збережено.');
    }

    public function edit(AiConnection $aiConnection, AiManager $ai): View
    {
        Gate::authorize('view', $aiConnection);

        [$models, $modelLoadFailed] = $this->availableModels($aiConnection, $ai);

        return view('settings.ai.edit', [
            'connection' => $aiConnection,
            'models' => $models,
            'modelLoadFailed' => $modelLoadFailed,
            'providerMetadata' => $ai->providerMetadata(),
        ]);
    }

    public function update(UpdateAiConnectionRequest $request, AiConnection $aiConnection): RedirectResponse
    {
        DB::transaction(function () use ($request, $aiConnection): void {
            $credentials = $aiConnection->credentials ?? [];

            if (filled($request->validated('api_key'))) {
                $credentials['api_key'] = $request->validated('api_key');
                $aiConnection->last_tested_at = null;
                $aiConnection->last_test_status = null;
            }

            $aiConnection->fill([
                'name' => $request->validated('name'),
                'provider' => $request->validated('provider'),
                'configuration' => $this->configurationFromRequest($request),
                'credentials' => $credentials,
                'is_enabled' => $request->boolean('is_enabled'),
            ])->save();

            if (! $aiConnection->is_enabled && $request->user()->default_ai_connection_id === $aiConnection->id) {
                $request->user()->forceFill(['default_ai_connection_id' => null])->save();
            } elseif ($aiConnection->is_enabled && $request->boolean('make_default')) {
                $request->user()->forceFill(['default_ai_connection_id' => $aiConnection->id])->save();
            }
        });

        return redirect()
            ->route('settings.ai.edit', $aiConnection)
            ->with('status', 'Підключення ШІ оновлено.');
    }

    public function destroy(Request $request, AiConnection $aiConnection): RedirectResponse
    {
        Gate::authorize('delete', $aiConnection);

        DB::transaction(function () use ($request, $aiConnection): void {
            if ($request->user()->default_ai_connection_id === $aiConnection->id) {
                $request->user()->forceFill(['default_ai_connection_id' => null])->save();
            }

            $aiConnection->delete();
        });

        return redirect()
            ->route('settings.ai.index')
            ->with('status', 'Підключення ШІ видалено.');
    }

    public function updateDefault(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_ai_connection_id' => [
                'nullable',
                'uuid',
                Rule::exists('ai_connections', 'id')
                    ->where('user_id', $request->user()->id)
                    ->where('is_enabled', true),
            ],
        ]);

        $request->user()->forceFill([
            'default_ai_connection_id' => $validated['default_ai_connection_id'] ?? null,
        ])->save();

        return redirect()
            ->route('settings.ai.index')
            ->with('status', 'Підключення за замовчуванням оновлено.');
    }

    public function testDraft(Request $request, AiManager $ai): JsonResponse
    {
        $validated = $this->validateTestRequest($request, apiKeyRequired: true);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $connection = new AiConnection([
            'user_id' => $request->user()->id,
            'name' => ($validated['name'] ?? null) ?: 'Draft',
            'provider' => $validated['provider'],
            'configuration' => $this->configurationFromRequest($request),
            'credentials' => ['api_key' => $validated['api_key']],
            'is_enabled' => true,
        ]);

        $result = $ai->testConnection($connection);

        return $this->testResultResponse($result->successful, $result->message);
    }

    public function testSaved(Request $request, AiConnection $aiConnection, AiManager $ai): JsonResponse
    {
        Gate::authorize('test', $aiConnection);

        $validated = $this->validateTestRequest($request, apiKeyRequired: false);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $credentials = $aiConnection->credentials ?? [];

        if (array_key_exists('api_key', $validated) && filled($validated['api_key'])) {
            $credentials['api_key'] = $validated['api_key'];
        }

        $connection = new AiConnection([
            'user_id' => $aiConnection->user_id,
            'name' => $aiConnection->name,
            'provider' => $validated['provider'] ?? $aiConnection->provider,
            'configuration' => $request->has('model')
                ? $this->configurationFromRequest($request)
                : ($aiConnection->configuration ?? []),
            'credentials' => $credentials,
            'is_enabled' => $aiConnection->is_enabled,
        ]);

        $result = $ai->testConnection($connection);

        $aiConnection->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $result->successful ? AiConnection::STATUS_SUCCESS : AiConnection::STATUS_FAILED,
        ])->save();

        return $this->testResultResponse($result->successful, $result->message, [
            'last_test_status' => $aiConnection->last_test_status,
            'last_tested_at' => $aiConnection->last_tested_at?->format('Y-m-d H:i'),
        ]);
    }

    private function configurationFromRequest(Request $request): array
    {
        return collect([
            'model' => $request->input('model'),
        ])->filter(fn ($value) => filled($value))->all();
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validateTestRequest(Request $request, bool $apiKeyRequired): array|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'provider' => [$apiKeyRequired ? 'required' : 'sometimes', Rule::in([AiConnection::PROVIDER_OPENAI])],
            'api_key' => [$apiKeyRequired ? 'required' : 'nullable', 'string', 'max:4096'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $validator->fails()) {
            return $validator->validated();
        }

        return response()->json([
            'successful' => false,
            'message' => 'Надані дані некоректні.',
            'errors' => collect($validator->errors()->toArray())
                ->except('api_key')
                ->all(),
        ], 422);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function testResultResponse(bool $successful, string $message, array $extra = []): JsonResponse
    {
        return response()->json([
            'successful' => $successful,
            'message' => $successful
                ? 'Підключення успішно перевірено.'
                : ($message ?: 'Не вдалося підключитися до провайдера.'),
        ] + $extra, $successful ? 200 : 422);
    }

    /**
     * @return array{0: list<string>, 1: bool}
     */
    private function availableModels(AiConnection $connection, AiManager $ai): array
    {
        if (! $connection->is_enabled || $connection->last_test_status !== AiConnection::STATUS_SUCCESS || ! $connection->hasCredentials()) {
            return [[], false];
        }

        try {
            return [$ai->models($connection), false];
        } catch (\Throwable) {
            return [[], true];
        }
    }
}
