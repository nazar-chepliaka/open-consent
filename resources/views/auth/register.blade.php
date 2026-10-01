@extends('layouts.auth')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Open Consent</h1>
                    <p class="text-body-secondary mb-4">Створіть обліковий запис для персонального архіву.</p>

                    <form method="post" action="{{ route('register.store') }}" class="d-flex flex-column gap-3">
                        @csrf

                        <div>
                            <label for="name" class="form-label">Ім'я</label>
                            <input id="name" class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Пароль</label>
                            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" autocomplete="new-password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="form-label">Підтвердження пароля</label>
                            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required>
                        </div>

                        <button class="btn btn-primary" type="submit">Створити обліковий запис</button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="{{ route('login') }}">Вже маєте обліковий запис? Увійти</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
