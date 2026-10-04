@props(['breadcrumbs' => []])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Open Consent') }}</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.sidebar')

    <div class="wrapper d-flex flex-column min-vh-100">
        @include('partials.header')

        <div class="body flex-grow-1">
            <main class="container-lg px-4">
                @include('partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

                @if (session('status'))
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @include('partials.footer')
    </div>
</body>
</html>
