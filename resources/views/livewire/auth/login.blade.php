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


        @if (Route::has('register'))
            <div class="mt-4 pt-3 border-top text-center small text-secondary">
                <span>{{ __('Belum memiliki akun?') }}</span>
                <a href="{{ route('register') }}" class="text-success text-decoration-none font-semibold ms-1" wire:navigate>{{ __('Daftar Sekarang') }}</a>
            </div>
        @endif
    </div>
</x-layouts::auth>
