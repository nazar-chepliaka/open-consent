<x-layouts.app :breadcrumbs="[
    ['title' => 'Налаштування', 'route' => 'settings.ai.index'],
    ['title' => 'Штучний інтелект', 'route' => 'settings.ai.index'],
    ['title' => $connection->name],
]">
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h3 mb-2">Редагувати підключення ШІ</h1>
            <p class="text-body-secondary mb-0">{{ $connection->name }}</p>
        </div>

        @if ($modelLoadFailed)
            <div class="alert alert-warning" role="alert">Не вдалося завантажити список моделей.</div>
        @endif

        <section class="card">
            <div class="card-body">
                <form method="post" action="{{ route('settings.ai.update', $connection) }}" class="row g-3" data-ai-test-form data-ai-test-url="{{ route('settings.ai.test', $connection) }}">
                    @csrf
                    @method('put')

                    <div class="col-12">
                        <label for="name" class="form-label">Назва підключення</label>
                        <input id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $connection->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="provider" class="form-label">Провайдер</label>
                        <select id="provider" name="provider" class="form-select @error('provider') is-invalid @enderror" required data-ai-test-watch data-ai-provider-select>
                            @foreach ($providerMetadata as $provider => $metadata)
                                <option value="{{ $provider }}" @selected(old('provider', $connection->provider) === $provider)>{{ $metadata['displayName'] }}</option>
                            @endforeach
                        </select>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @php
                            $selectedProviderMetadata = $providerMetadata[old('provider', $connection->provider)] ?? null;
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
                        <input id="api_key" class="form-control @error('api_key') is-invalid @enderror" type="password" name="api_key" autocomplete="off" data-ai-test-watch>
                        <div class="form-text">API key налаштовано. Залиште поле порожнім, щоб не змінювати ключ.</div>
                        @error('api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8 col-lg-6">
                        <label for="model" class="form-label">Модель</label>
                        @php
                            $selectedModel = old('model', $connection->selectedModel());
                            $selectedModel = is_string($selectedModel) ? $selectedModel : null;
                            $modelOptions = collect($models)
                                ->when(filled($selectedModel) && ! in_array($selectedModel, $models, true), fn ($collection) => $collection->prepend($selectedModel))
                                ->unique()
                                ->values();
                        @endphp
                        <select id="model" name="model" class="form-select @error('model') is-invalid @enderror" data-ai-test-watch>
                            <option value="" @selected(blank($selectedModel))>Автоматично (рекомендовано)</option>
                            @foreach ($modelOptions as $model)
                                <option value="{{ $model }}" @selected($selectedModel === $model)>{{ $model }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Open Consent автоматично вибере відповідну модель для операції.
                            За потреби можна вибрати конкретну модель вручну.
                        </div>
                        @if (count($models) === 0)
                            <div class="form-text">Список моделей зʼявиться після успішної перевірки підключення.</div>
                        @endif
                        @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input id="is_enabled" class="form-check-input" type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $connection->is_enabled))>
                            <label class="form-check-label" for="is_enabled">Підключення увімкнено</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input id="make_default" class="form-check-input" type="checkbox" name="make_default" value="1" @checked(old('make_default', auth()->user()->default_ai_connection_id === $connection->id))>
                            <label class="form-check-label" for="make_default">Зробити підключенням за замовчуванням</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="small" data-ai-test-status aria-live="polite"></div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="cil-save me-1"></i>
                            Зберегти
                        </button>
                        <button class="btn btn-outline-secondary" type="button" data-ai-test-button>
                            <i class="cil-check-circle me-1"></i>
                            <span data-ai-test-label>Перевірити</span>
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('settings.ai.index') }}">Скасувати</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Видалення</h2>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('settings.ai.destroy', $connection) }}">
                    @csrf
                    @method('delete')
                    <button class="btn btn-outline-danger" type="submit">Видалити підключення</button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
