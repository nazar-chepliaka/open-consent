<x-layouts.app :breadcrumbs="[
    ['title' => 'Архіви'],
]">
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h3 mb-2">Архіви</h1>
            <p class="text-body-secondary mb-0">Приватні та спільні сховища документів Open Consent.</p>
        </div>

        <section class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Новий архів</h2>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('vaults.store') }}" class="row gy-3 gx-3 align-items-end">
                    @csrf
                    <div class="col-md-8 col-lg-6">
                        <label for="name" class="form-label">Назва</label>
                        <input id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-primary" type="submit">
                            <i class="cil-plus me-1"></i>
                            Створити
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Список архівів</h2>
            </div>
            <div class="card-body p-0">
                @if ($vaults->isEmpty())
                    <div class="p-4 text-body-secondary">Архівів ще немає.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th class="text-end">Документи</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vaults as $vault)
                                    <tr>
                                        <td><a href="{{ route('vaults.show', $vault) }}">{{ $vault->name }}</a></td>
                                        <td class="text-end">{{ $vault->archive_entries_count }}</td>
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
