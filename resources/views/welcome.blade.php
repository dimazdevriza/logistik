<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-light min-vh-100 d-flex align-items-center justify-content-center py-5">
        @if (Route::has('login'))
            @auth
                <script>window.location='{{ route('dashboard') }}';</script>
            @else
                <div class="container" style="max-width: 420px;">
                    <div class="text-center mb-4">
                        <h2 class="fw-black text-success font-outfit">D'Royal Village</h2>
                    </div>
                    <div class="card border-0 shadow-lg rounded-4 p-4 bg-body">
                        <x-auth-header :title="__('Masuk ke Akun Anda')" :description="__('Masukkan email dan kata sandi Anda di bawah ini untuk masuk')" />

                        <x-auth-session-status class="text-center mb-3" :status="session('status')" />

                        <form method="POST" action="{{ route('login.store') }}">
                            @csrf

                            <!-- Email Address -->
                            <div class="mb-3">
                                <label for="email" class="form-label font-semibold small">{{ __('Alamat Email') }}</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="email@example.com"
                                />
                                @error('email') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label font-semibold small mb-0">{{ __('Kata Sandi') }}</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="small text-success text-decoration-none" wire:navigate>
                                            {{ __('Lupa kata sandi?') }}
                                        </a>
                                    @endif
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="form-control"
                                    required
                                    autocomplete="current-password"
                                    placeholder="{{ __('Kata Sandi') }}"
                                />
                                @error('password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Remember Me -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label small text-secondary" for="remember">
                                    {{ __('Ingat Saya') }}
                                </label>
                            </div>

                            <button type="submit" class="btn btn-success w-100 font-semibold py-2" data-test="login-button">
                                {{ __('Masuk') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        @endif
    </body>
</html>
