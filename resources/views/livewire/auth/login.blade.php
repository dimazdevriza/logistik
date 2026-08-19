<x-layouts::auth :title="__('Masuk')">
    <div>
        <!-- Session Status -->
        <x-auth-session-status class="text-center mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label font-semibold small text-body">{{ __('Alamat Email') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="form-control"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="admin@droyal.com"
                />
                @error('email') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <!-- Password -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label font-semibold small text-body mb-0">{{ __('Kata Sandi') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-success text-decoration-none font-medium" wire:navigate>
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
                    placeholder="••••••••"
                />
                @error('password') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <!-- Remember Me -->
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small text-secondary" for="remember">
                    {{ __('Ingat Saya') }}
                </label>
            </div>

            <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs" data-test="login-button">
                {{ __('Masuk ke Portal') }}
            </button>
        </form>

        <!-- Divider -->
        <div class="d-flex align-items-center my-3">
            <hr class="flex-grow-1 border-secondary opacity-25 my-0">
            <span class="px-3 extra-small text-secondary fw-semibold text-uppercase">atau</span>
            <hr class="flex-grow-1 border-secondary opacity-25 my-0">
        </div>

        <!-- Google OAuth Button -->
        <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100 font-semibold py-2.5 rounded-3 shadow-xs d-flex align-items-center justify-content-center gap-2">
            <svg width="18" height="18" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Masuk dengan Google</span>
        </a>

        @if (Route::has('register'))
            <div class="mt-4 pt-3 border-top text-center small text-secondary">
                <span>{{ __('Belum memiliki akun?') }}</span>
                <a href="{{ route('register') }}" class="text-success text-decoration-none font-semibold ms-1" wire:navigate>{{ __('Daftar Sekarang') }}</a>
            </div>
        @endif
    </div>
</x-layouts::auth>
