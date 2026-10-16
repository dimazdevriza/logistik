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
                            <div class="mb-3" x-data="{ showPassword: false }">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label font-semibold small mb-0">{{ __('Kata Sandi') }}</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="small text-success text-decoration-none" wire:navigate>
                                            {{ __('Lupa kata sandi?') }}
                                        </a>
                                    @endif
                                </div>
                                <div class="position-relative">
                                    <input
                                        id="password"
                                        name="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        type="password"
                                        class="form-control pe-5"
                                        required
                                        autocomplete="current-password"
                                        placeholder="{{ __('Kata Sandi') }}"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary text-decoration-none d-flex align-items-center justify-content-center p-0"
                                        style="width: 44px; height: 100%; border: none; background: transparent; z-index: 5;"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? '{{ __('Sembunyikan kata sandi') }}' : '{{ __('Tampilkan kata sandi') }}'"
                                        :title="showPassword ? '{{ __('Sembunyikan kata sandi') }}' : '{{ __('Tampilkan kata sandi') }}'"
                                    >
                                        <svg x-show="!showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg x-show="showPassword" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                            <line x1="2" x2="22" y1="2" y2="22"/>
                                        </svg>
                                    </button>
                                </div>
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
