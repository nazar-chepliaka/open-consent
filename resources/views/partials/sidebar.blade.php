@php
    $navItems = [
        ['title' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'cil-speedometer'],
        ['title' => 'Архіви', 'route' => 'vaults.index', 'icon' => 'cil-folder-open'],
        ['title' => 'Документи', 'route' => null, 'icon' => 'cil-description'],
        ['title' => 'Правовідносини', 'route' => null, 'icon' => 'cil-link'],
        ['title' => 'Згоди', 'route' => null, 'icon' => 'cil-check-circle'],
        ['title' => 'Налаштування', 'route' => 'settings.ai.index', 'icon' => 'cil-settings'],
    ];
@endphp

<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <a class="sidebar-brand text-decoration-none" href="{{ route('dashboard') }}">
            <span class="sidebar-brand-full fw-semibold">
                <span class="app-brand-lockup">
                    <img class="sidebar-brand-logo" src="{{ asset('images/logo.svg') }}" alt="">
                    <span>Open Consent</span>
                </span>
            </span>
            <span class="sidebar-brand-narrow">
                <img class="sidebar-brand-logo" src="{{ asset('images/logo.svg') }}" alt="Open Consent">
            </span>
        </a>
    </div>

    <ul class="sidebar-nav" data-coreui="navigation">
        @foreach ($navItems as $item)
            @php
                $hasRoute = $item['route'] && Route::has($item['route']);
                $isActive = $hasRoute && request()->routeIs($item['route']);
            @endphp

            <li class="nav-item">
                @if ($hasRoute)
                    <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <i class="nav-icon {{ $item['icon'] }}"></i>
                        {{ $item['title'] }}
                    </a>
                @else
                    <span class="nav-link disabled" aria-disabled="true">
                        <i class="nav-icon {{ $item['icon'] }}"></i>
                        {{ $item['title'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
