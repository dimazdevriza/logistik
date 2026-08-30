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
                <label for="password" class="form-label font-semibold small text-body">{{ __('Kata Sandi') }}</label>
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

        <div class="d-flex align-items-center gap-3 my-4" aria-hidden="true">
            <hr class="flex-grow-1 my-0">
            <span class="small text-secondary">atau</span>
            <hr class="flex-grow-1 my-0">
        </div>

        <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100 font-semibold py-2.5 rounded-3 d-flex align-items-center justify-content-center gap-2" data-test="google-login-button">
            <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.26-.16-1.86H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.9c1.7-1.56 2.7-3.86 2.7-6.6Z"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.2l-2.9-2.26c-.81.54-1.84.86-3.06.86-2.34 0-4.33-1.58-5.04-3.7H.96v2.32A9 9 0 0 0 9 18Z"/>
                <path fill="#FBBC05" d="M3.96 10.7A5.4 5.4 0 0 1 3.68 9c0-.59.1-1.16.28-1.7V4.98H.96A9 9 0 0 0 0 9c0 1.45.35 2.82.96 4.02l3-2.32Z"/>
                <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.34L15.02 2.34A8.64 8.64 0 0 0 9 0 9 9 0 0 0 .96 4.98l3 2.32C4.67 5.17 6.66 3.58 9 3.58Z"/>
            </svg>
            {{ __('Masuk dengan Google') }}
        </a>

        @if (Route::has('register'))
            <div class="mt-4 pt-3 border-top text-center small text-secondary">
                <span>{{ __('Belum memiliki akun?') }}</span>
                <a href="{{ route('register') }}" class="text-success text-decoration-none font-semibold ms-1" wire:navigate>{{ __('Daftar Sekarang') }}</a>
            </div>
        @endif
    </div>
</x-layouts::auth>
