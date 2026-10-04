<x-layouts.app :breadcrumbs="[
    ['title' => 'Налаштування', 'route' => 'settings.ai.index'],
    ['title' => 'Штучний інтелект', 'route' => 'settings.ai.index'],
    ['title' => 'Нове підключення'],
]">
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h3 mb-2">Додати підключення ШІ</h1>
            <p class="text-body-secondary mb-0">Облікові дані зберігаються зашифрованими та не показуються після збереження.</p>
        </div>

        <section class="card">
            <div class="card-body">
                <form method="post" action="{{ route('settings.ai.store') }}" class="row g-3" data-ai-test-form data-ai-test-url="{{ route('settings.ai.test-draft') }}">
                    @csrf

                    <div class="col-12">
                        <label for="name" class="form-label">Назва підключення</label>
                        <input id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', 'Мій OpenAI') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="provider" class="form-label">Провайдер</label>
                        <select id="provider" name="provider" class="form-select @error('provider') is-invalid @enderror" required data-ai-test-watch data-ai-provider-select>
                            @foreach ($providerMetadata as $provider => $metadata)
                                <option value="{{ $provider }}" @selected(old('provider', 'openai') === $provider)>{{ $metadata['displayName'] }}</option>
                            @endforeach
                        </select>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php
                            $selectedProviderMetadata = $providerMetadata[old('provider', 'openai')] ?? null;
                            $hasProviderHelp = filled($selectedProviderMetadata['displayName'] ?? null)
                                && filled($selectedProviderMetadata['credentialsHelpUrl'] ?? null)
                                && filled($selectedProviderMetadata['credentialsHelpLabel'] ?? null);
                        @endphp
                        <div class="form-text mt-2 ps-3 border-start" data-ai-provider-help data-ai-provider-help-options='@json($providerMetadata)' @hidden(! $hasProviderHelp)>
                            @if ($hasProviderHelp)
                                <div>Для {{ $selectedProviderMetadata['displayName'] }}:</div>
                                <a class="d-inline-flex align-items-center gap-1" href="{{ $selectedProviderMetadata['credentialsHelpUrl'] }}" target="_blank" rel="noopener noreferrer">
                                    <i class="cil-external-link" aria-hidden="true"></i>
                                    <span>{{ $selectedProviderMetadata['credentialsHelpLabel'] }}</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="api_key" class="form-label">API key</label>
                        <input id="api_key" class="form-control @error('api_key') is-invalid @enderror" type="password" name="api_key" autocomplete="off" required data-ai-test-watch>
                        @error('api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input id="make_default" class="form-check-input" type="checkbox" name="make_default" value="1" @checked(old('make_default'))>
                            <label class="form-check-label" for="make_default">Зробити підключенням за замовчуванням</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="small" data-ai-test-status aria-live="polite"></div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-ai-test-button>
                            <i class="cil-check-circle me-1"></i>
                            <span data-ai-test-label>Перевірити підключення</span>
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <i class="cil-save me-1"></i>
                            Зберегти
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('settings.ai.index') }}">Скасувати</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
