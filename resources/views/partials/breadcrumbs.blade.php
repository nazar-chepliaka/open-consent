@if (! empty($breadcrumbs))
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            @foreach ($breadcrumbs as $breadcrumb)
                @php
                    $isLast = $loop->last;
                    $hasRoute = isset($breadcrumb['route']) && Route::has($breadcrumb['route']);
                @endphp

                <li class="breadcrumb-item {{ $isLast ? 'active' : '' }}" @if ($isLast) aria-current="page" @endif>
                    @if (! $isLast && $hasRoute)
                        <a href="{{ route($breadcrumb['route'], $breadcrumb['parameters'] ?? []) }}">{{ $breadcrumb['title'] }}</a>
                    @else
                        {{ $breadcrumb['title'] }}
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
