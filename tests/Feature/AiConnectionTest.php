<?php

use App\Models\AiConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('a user can create an encrypted openai ai connection without exposing the key', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('settings.ai.store'), [
            'name' => 'Мій OpenAI',
            'provider' => 'openai',
            'api_key' => 'sk-secret-value',
            'make_default' => '1',
        ]);

    $connection = AiConnection::query()->firstOrFail();

    $response->assertRedirect(route('settings.ai.edit', $connection, absolute: false));

    expect($connection->user_id)->toBe($user->id)
        ->and($connection->provider)->toBe('openai')
        ->and($connection->selectedModel())->toBeNull()
        ->and($connection->configuration)->not->toHaveKey('model')
        ->and($connection->credentials['api_key'])->toBe('sk-secret-value')
        ->and($connection->toArray())->not->toHaveKey('credentials')
        ->and($user->refresh()->default_ai_connection_id)->toBe($connection->id);

    $raw = AiConnection::query()->whereKey($connection->id)->toBase()->first();

    expect($raw->credentials)->not->toContain('sk-secret-value');
});

test('ai connection create form shows provider-specific credential help', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.ai.create'))
        ->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('data-ai-provider-select')
        ->and($content)->toContain('data-ai-provider-help')
        ->and($content)->toContain('Для OpenAI:')
        ->and($content)->toContain('Інструкція з отримання API-ключа OpenAI')
        ->and($content)->toContain('https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key')
        ->and($content)->toContain('target="_blank"')
        ->and($content)->toContain('rel="noopener noreferrer"')
        ->and(strpos($content, 'data-ai-provider-help'))->toBeLessThan(strpos($content, 'id="api_key"'));
});

test('ai connection edit form shows provider-specific credential help', function () {
    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'Existing OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'is_enabled' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('settings.ai.edit', $connection))
        ->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('Для OpenAI:')
        ->and($content)->toContain('Інструкція з отримання API-ключа OpenAI')
        ->and(strpos($content, 'data-ai-provider-help'))->toBeLessThan(strpos($content, 'id="api_key"'));
});

test('ai connection edit form defaults model selection to automatic', function () {
    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'Existing OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'is_enabled' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('settings.ai.edit', $connection))
        ->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('Автоматично (рекомендовано)')
        ->and($content)->toContain('Open Consent автоматично вибере відповідну модель для операції.')
        ->and($content)->toContain('За потреби можна вибрати конкретну модель вручну.')
        ->and(strpos($content, 'Автоматично (рекомендовано)'))->toBeLessThan(strpos($content, '</select>'));
});

test('editing with blank api key preserves the encrypted credential', function () {
    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'Existing OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'configuration' => ['model' => 'gpt-5-mini'],
        'is_enabled' => true,
    ]);

    $this->actingAs($user)
        ->put(route('settings.ai.update', $connection), [
            'name' => 'Existing OpenAI Updated',
            'provider' => 'openai',
            'api_key' => '',
            'model' => 'gpt-5',
            'is_enabled' => '1',
        ])
        ->assertRedirect(route('settings.ai.edit', $connection, absolute: false));

    expect($connection->refresh()->credentials['api_key'])->toBe('sk-original')
        ->and($connection->selectedModel())->toBe('gpt-5');
});

test('blank model preference keeps an ai connection fully usable with automatic model selection', function () {
    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'Existing OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'configuration' => ['model' => 'gpt-5-mini'],
        'is_enabled' => true,
    ]);

    $this->actingAs($user)
        ->put(route('settings.ai.update', $connection), [
            'name' => 'Existing OpenAI',
            'provider' => 'openai',
            'api_key' => '',
            'model' => '',
            'is_enabled' => '1',
        ])
        ->assertRedirect(route('settings.ai.edit', $connection, absolute: false));

    expect($connection->refresh()->credentials['api_key'])->toBe('sk-original')
        ->and($connection->hasCredentials())->toBeTrue()
        ->and($connection->selectedModel())->toBeNull()
        ->and($connection->configuration)->not->toHaveKey('model');
});

test('ai connections list shows automatic instead of an incomplete model state', function () {
    $user = User::factory()->create();
    $user->aiConnections()->create([
        'name' => 'Automatic OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'is_enabled' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('settings.ai.index'))
        ->assertOk();

    expect($response->getContent())->toContain('Автоматично')
        ->and($response->getContent())->not->toContain('<td>Не вибрано</td>');
});

test('a user cannot set another users connection as default', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $connection = $owner->aiConnections()->create([
        'name' => 'Owner OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-owner'],
        'is_enabled' => true,
    ]);

    $this->actingAs($stranger)
        ->put(route('settings.ai.default.update'), [
            'default_ai_connection_id' => $connection->id,
        ])
        ->assertSessionHasErrors('default_ai_connection_id');

    expect($stranger->refresh()->default_ai_connection_id)->toBeNull();
});

test('testing a saved connection records normalized success status and lists models', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response([
            'data' => [
                ['id' => 'gpt-5-mini'],
                ['id' => 'gpt-5'],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-test'],
        'is_enabled' => true,
    ]);

    $response = $this->actingAs($user)
        ->post(route('settings.ai.test', $connection))
        ->assertOk()
        ->assertJson([
            'successful' => true,
            'message' => 'Підключення успішно перевірено.',
            'last_test_status' => AiConnection::STATUS_SUCCESS,
        ]);

    expect($connection->refresh()->last_test_status)->toBe(AiConnection::STATUS_SUCCESS)
        ->and($connection->last_tested_at)->not->toBeNull()
        ->and($response->json('last_tested_at'))->toBe($connection->last_tested_at->format('Y-m-d H:i'));

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-test'));
});

test('test endpoint returns json for an unsaved connection', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['data' => []]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('settings.ai.test-draft'), [
            'name' => 'Draft OpenAI',
            'provider' => 'openai',
            'api_key' => 'sk-draft',
        ])
        ->assertOk()
        ->assertJson([
            'successful' => true,
            'message' => 'Підключення успішно перевірено.',
        ]);

    expect($response->headers->get('content-type'))->toContain('application/json');
});

test('successful unsaved connection test does not persist credentials', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['data' => []]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.ai.test-draft'), [
            'name' => 'Draft OpenAI',
            'provider' => 'openai',
            'api_key' => 'sk-success-draft',
        ])
        ->assertOk();

    expect(AiConnection::query()->count())->toBe(0);
});

test('failed unsaved connection test does not persist credentials', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['error' => ['message' => 'bad key']], 401),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.ai.test-draft'), [
            'name' => 'Draft OpenAI',
            'provider' => 'openai',
            'api_key' => 'sk-failed-draft',
        ])
        ->assertUnprocessable()
        ->assertJson([
            'successful' => false,
        ]);

    expect(AiConnection::query()->count())->toBe(0);
});

test('api key is not returned in test response', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['data' => []]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('settings.ai.test-draft'), [
            'provider' => 'openai',
            'api_key' => 'sk-never-return-this',
        ])
        ->assertOk();

    expect($response->getContent())->not->toContain('sk-never-return-this')
        ->and($response->json())->not->toHaveKey('api_key');
});

test('stored connection can be tested by its owner with stored credentials', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['data' => []]),
    ]);

    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-owner-stored'],
        'is_enabled' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('settings.ai.test', $connection), [
            'provider' => 'openai',
            'api_key' => '',
        ])
        ->assertOk()
        ->assertJson(['successful' => true]);

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-owner-stored'));
});

test('stored connection replacement key can be tested without saving it', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['data' => []]),
    ]);

    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'is_enabled' => true,
    ]);

    $this->actingAs($user)
        ->postJson(route('settings.ai.test', $connection), [
            'provider' => 'openai',
            'api_key' => 'sk-replacement',
        ])
        ->assertOk();

    expect($connection->refresh()->credentials['api_key'])->toBe('sk-original');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-replacement'));
});

test('failed stored connection replacement key test does not save it', function () {
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['error' => ['message' => 'bad key']], 401),
    ]);

    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-original'],
        'is_enabled' => true,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('settings.ai.test', $connection), [
            'provider' => 'openai',
            'api_key' => 'sk-bad-replacement',
        ])
        ->assertUnprocessable()
        ->assertJson([
            'successful' => false,
            'last_test_status' => AiConnection::STATUS_FAILED,
        ]);

    expect($connection->refresh()->credentials['api_key'])->toBe('sk-original')
        ->and($connection->last_test_status)->toBe(AiConnection::STATUS_FAILED)
        ->and($connection->last_tested_at)->not->toBeNull()
        ->and($response->json('last_tested_at'))->toBe($connection->last_tested_at->format('Y-m-d H:i'));
});

test('another users connection cannot be tested', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $connection = $owner->aiConnections()->create([
        'name' => 'Owner OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-owner'],
        'is_enabled' => true,
    ]);

    $this->actingAs($stranger)
        ->postJson(route('settings.ai.test', $connection), [
            'provider' => 'openai',
        ])
        ->assertForbidden();
});

test('invalid test input produces safe json validation errors', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('settings.ai.test-draft'), [
            'provider' => 'unsupported',
            'api_key' => ['sk-secret-array'],
        ])
        ->assertUnprocessable()
        ->assertJson([
            'successful' => false,
            'message' => 'Надані дані некоректні.',
        ]);

    expect($response->json('errors'))->toHaveKey('provider')
        ->and($response->json('errors'))->not->toHaveKey('api_key')
        ->and($response->getContent())->not->toContain('sk-secret-array');
});

test('disabling the default connection clears the default preference', function () {
    $user = User::factory()->create();
    $connection = $user->aiConnections()->create([
        'name' => 'OpenAI',
        'provider' => 'openai',
        'credentials' => ['api_key' => 'sk-test'],
        'is_enabled' => true,
    ]);
    $user->forceFill(['default_ai_connection_id' => $connection->id])->save();

    $this->actingAs($user)
        ->put(route('settings.ai.update', $connection), [
            'name' => 'OpenAI',
            'provider' => 'openai',
            'api_key' => '',
            'model' => '',
        ])
        ->assertRedirect(route('settings.ai.edit', $connection, absolute: false));

    expect($connection->refresh()->is_enabled)->toBeFalse()
        ->and($user->refresh()->default_ai_connection_id)->toBeNull();
});
