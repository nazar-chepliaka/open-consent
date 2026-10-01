@extends('layouts.auth')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Open Consent</h1>
                    <p class="text-body-secondary mb-4">Увійдіть, щоб працювати з архівами.</p>

                    <form method="post" action="{{ route('login.store') }}" class="d-flex flex-column gap-3">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Пароль</label>
                            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" autocomplete="current-password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input id="remember" class="form-check-input" type="checkbox" name="remember" value="1">
                            <label class="form-check-label" for="remember">Запам'ятати мене</label>
                        </div>

                        <button class="btn btn-primary" type="submit">Увійти</button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="{{ route('register') }}">Створити обліковий запис</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
