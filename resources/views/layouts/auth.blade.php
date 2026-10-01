<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Open Consent') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="min-vh-100 d-flex align-items-center py-4 bg-body-tertiary">
        <div class="container">
            @yield('content')
        </div>
    </main>
</body>
</html>
