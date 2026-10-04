<x-layouts.app :breadcrumbs="[
    ['title' => 'Налаштування'],
    ['title' => 'Штучний інтелект'],
]">
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h3 mb-2">Налаштування</h1>
            <p class="text-body-secondary mb-0">Штучний інтелект</p>
        </div>

        <section class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Підключення за замовчуванням</h2>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('settings.ai.default.update') }}" class="row gy-3 gx-3 align-items-end">
                    @csrf
                    @method('put')

                    <div class="col-md-8 col-lg-6">
                        <label for="default_ai_connection_id" class="form-label">Підключення</label>
                        <select id="default_ai_connection_id" name="default_ai_connection_id" class="form-select @error('default_ai_connection_id') is-invalid @enderror">
                            <option value="">Не вибрано</option>
                            @foreach ($connections->where('is_enabled', true) as $connection)
                                <option value="{{ $connection->id }}" @selected(old('default_ai_connection_id', $defaultConnectionId) === $connection->id)>
                                    {{ $connection->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('default_ai_connection_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-primary" type="submit">
                            <i class="cil-save me-1"></i>
                            Зберегти
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Підключення</h2>
                @if ($connections->isNotEmpty())
                    <a class="btn btn-sm btn-primary" href="{{ route('settings.ai.create') }}">
                        <i class="cil-plus me-1"></i>
                        Додати підключення
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($connections->isEmpty())
                    <div class="p-4">
                        <p class="text-body-secondary mb-3">Підключення ШІ ще не налаштовано.</p>
                        <a class="btn btn-primary" href="{{ route('settings.ai.create') }}">
                            <i class="cil-plus me-1"></i>
                            Додати підключення
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Провайдер</th>
                                    <th>Модель</th>
                                    <th>Статус</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($connections as $connection)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $connection->name }}</div>
                                            @if ($defaultConnectionId === $connection->id)
                                                <span class="badge text-bg-primary">За замовчуванням</span>
                                            @endif
                                            @unless ($connection->is_enabled)
                                                <span class="badge text-bg-secondary">Вимкнено</span>
                                            @endunless
                                        </td>
                                        <td>OpenAI</td>
                                        <td>{{ $connection->selectedModel() ?? 'Автоматично' }}</td>
                                        <td data-ai-test-status-cell>
                                            @include('settings.ai.partials.connection-status', ['connection' => $connection])
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('settings.ai.edit', $connection) }}">Редагувати</a>
                                                <form method="post" action="{{ route('settings.ai.test', $connection) }}" data-ai-test-form data-ai-test-url="{{ route('settings.ai.test', $connection) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-ai-test-button>
                                                        <span data-ai-test-label>Перевірити</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-layouts.app>
