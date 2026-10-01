<header class="header header-sticky p-0 mb-4">
    <div class="container-fluid border-bottom px-4">
        <button class="header-toggler" type="button" data-coreui-toggle="sidebar" data-coreui-target="#sidebar" aria-label="Toggle navigation">
            <i class="cil-menu"></i>
        </button>

        <div class="ms-auto dropdown">
            <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center gap-2 px-0" type="button" data-coreui-toggle="dropdown" aria-expanded="false">
                <i class="cil-user"></i>
                <span>{{ auth()->user()?->name ?? 'User' }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if (Route::has('logout'))
                    <li>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item" type="submit">
                                <i class="cil-account-logout me-2"></i>
                                Logout
                            </button>
                        </form>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</header>
