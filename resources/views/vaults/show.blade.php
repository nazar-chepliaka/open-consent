<x-layouts.app :breadcrumbs="[
    ['title' => 'Архіви', 'route' => 'vaults.index'],
    ['title' => $vault->name],
]">
    <div class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-center justify-content-between">
            <div>
                <h1 class="h3 mb-1">{{ $vault->name }}</h1>
                <p class="text-body-secondary mb-0">Документи, додані до архіву.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('vaults.documents.create', $vault) }}">
                <i class="cil-cloud-upload me-1"></i>
                Завантажити документ
            </a>
        </div>

        <section class="card">
            <div class="card-body p-0">
                @if ($entries->isEmpty())
                    <div class="p-4 text-body-secondary">У цьому архіві ще немає документів.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Версія</th>
                                    <th>Додано</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    <tr>
                                        <td><a href="{{ route('documents.show', $entry->documentVersion->document) }}">{{ $entry->title }}</a></td>
                                        <td>{{ $entry->documentVersion->version_label ?? $entry->documentVersion->id }}</td>
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
