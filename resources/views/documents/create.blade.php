<x-layouts.app :breadcrumbs="[
    ['title' => 'Архіви', 'route' => 'vaults.index'],
    ['title' => $vault->name, 'route' => 'vaults.show', 'parameters' => $vault],
    ['title' => 'Завантаження документа'],
]">
    <div class="d-flex flex-column gap-4">
        <div>
            <h1 class="h3 mb-2">Завантажити документ</h1>
            <p class="text-body-secondary mb-0">Оригінал буде збережено як незмінну редакцію документа.</p>
        </div>

        <section class="card">
            <div class="card-body">
                <form method="post" action="{{ route('vaults.documents.store', $vault) }}" enctype="multipart/form-data" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <label for="title" class="form-label">Назва документа</label>
                        <input id="title" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="file" class="form-label">Файл</label>
                        <input id="file" class="form-control @error('file') is-invalid @enderror" type="file" name="file" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="cil-cloud-upload me-1"></i>
                            Завантажити
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('vaults.show', $vault) }}">Скасувати</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
