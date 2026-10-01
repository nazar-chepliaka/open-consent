<x-layouts.app :breadcrumbs="[
    ['title' => 'Архіви', 'route' => 'vaults.index'],
    ['title' => $document->ownerVault?->name ?? 'Документи', 'route' => $document->ownerVault ? 'vaults.show' : null, 'parameters' => $document->ownerVault],
    ['title' => $document->title],
]">
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h3 mb-2">{{ $document->title }}</h1>
            <p class="text-body-secondary mb-0">Редакції документа та контрольні хеші оригінальних файлів.</p>
        </div>

        <section class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Додати редакцію</h2>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data" class="row gy-3 gx-3 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label for="version_label" class="form-label">Позначка версії</label>
                        <input id="version_label" class="form-control @error('version_label') is-invalid @enderror" name="version_label" value="{{ old('version_label') }}" required>
                        @error('version_label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-5">
                        <label for="file" class="form-label">Файл</label>
                        <input id="file" class="form-control @error('file') is-invalid @enderror" type="file" name="file" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-primary" type="submit">
                            <i class="cil-plus me-1"></i>
                            Додати
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Редакції</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Версія</th>
                                <th>Зафіксовано</th>
                                <th>SHA-256</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->versions as $version)
                                <tr>
                                    <td>{{ $version->version_label ?? $version->id }}</td>
                                    <td>{{ $version->captured_at?->toDateTimeString() }}</td>
                                    <td><code class="hash-cell">{{ $version->originalObject?->content_hash }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
