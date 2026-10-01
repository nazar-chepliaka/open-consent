<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Open Consent') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <div class="min-vh-100 d-flex flex-column">
        <header class="py-3">
            <div class="container d-flex align-items-center justify-content-between gap-3">
                <a class="fw-semibold text-body text-decoration-none" href="{{ url('/') }}">Open Consent</a>

                <nav class="d-flex align-items-center gap-2" aria-label="Головна навігація">
                    @guest
                        @if (Route::has('login'))
                            <a class="btn btn-link text-decoration-none px-2" href="{{ route('login') }}">Увійти</a>
                        @endif

                        @if (Route::has('register'))
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('register') }}">Створити обліковий запис</a>
                        @endif
                    @else
                        @if (Route::has('dashboard'))
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard') }}">Перейти до Open Consent</a>
                        @endif
                    @endguest
                </nav>
            </div>
        </header>

        <main class="flex-grow-1 d-flex align-items-center py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-7">
                        <div class="card">
                            <div class="card-body p-4 p-md-5">
                                @auth
                                    <p class="text-body-secondary mb-2">Вітаємо, {{ auth()->user()->name }}.</p>
                                @endauth

                                <h1 class="display-6 fw-semibold mb-3">Open Consent</h1>
                                <p class="lead text-body-secondary mb-4">
                                    Персональний архів правових документів, договорів і згод.
                                </p>
                                <p class="text-body-secondary mb-4">
                                    Open Consent допомагає впорядковувати правові документи та історію наданих згод.
                                </p>

                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    @guest
                                        @if (Route::has('register'))
                                            <a class="btn btn-primary" href="{{ route('register') }}">Створити обліковий запис</a>
                                        @endif

                                        @if (Route::has('login'))
                                            <a class="btn btn-outline-secondary" href="{{ route('login') }}">Увійти</a>
                                        @endif
                                    @else
                                        @if (Route::has('dashboard'))
                                            <a class="btn btn-primary" href="{{ route('dashboard') }}">Перейти до Open Consent</a>
                                        @endif
                                    @endguest
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
