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
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                />
                @error('email')
                    <div id="email-error" class="alert alert-danger py-2 px-3 mt-2 mb-0 small" role="alert">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-3" x-data="{ showPassword: false }">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label font-semibold small text-body mb-0">{{ __('Kata Sandi') }}</label>
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
                        placeholder="••••••••"
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

        <div class="mt-4 pt-3 border-top text-center small text-secondary">
            <span>Butuh petunjuk operasional?</span>
            <a href="{{ Route::has('logistik.guide') ? route('logistik.guide') : url('panduan') }}" class="text-success text-decoration-none font-semibold ms-1" wire:navigate>Baca Panduan Sistem</a>
        </div>
    </div>
</x-layouts::auth>
