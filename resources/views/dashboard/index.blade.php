<x-layouts.app :breadcrumbs="[
    ['title' => 'Dashboard'],
]">
    <div class="d-flex flex-column gap-4">
        <section>
            <h1 class="h3 mb-2">Open Consent</h1>
            <p class="text-body-secondary mb-0">
                Персональний архів правових документів, правовідносин і згод з перевірюваними джерелами.
            </p>
        </section>

        <div class="row g-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-body-secondary small mb-1">Архіви</div>
                        <div class="fs-4 fw-semibold">{{ $vaultCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-body-secondary small mb-1">Документи</div>
                        <div class="fs-4 fw-semibold">{{ $documentCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        <section class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0">Останні документи</h2>
                @if (Route::has('vaults.index'))
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('vaults.index') }}">Всі архіви</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($latestEntries->isEmpty())
                    <div class="p-4 text-body-secondary">
                        Документів ще немає. Створіть архів і завантажте перший файл.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Архів</th>
                                    <th>Додано</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestEntries as $entry)
                                    <tr>
                                        <td>
                                            <a href="{{ route('documents.show', $entry->documentVersion->document) }}">
                                                {{ $entry->title }}
                                            </a>
                                        </td>
                                        <td>{{ $entry->vault->name }}</td>
                                        <td>{{ $entry->added_at->toDateTimeString() }}</td>
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
