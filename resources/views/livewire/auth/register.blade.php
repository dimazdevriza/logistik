<x-layouts::auth :title="__('Pendaftaran Akun')">
    <div>
        <x-auth-header :title="__('Buat Akun Baru')" :description="__('Masukkan detail Anda di bawah ini untuk membuat akun')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label font-semibold small text-body">{{ __('Nama Lengkap') }}</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    class="form-control"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nama Lengkap"
                />
                @error('name') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

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
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                @error('password') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="password_confirmation" class="form-label font-semibold small text-body">{{ __('Konfirmasi Kata Sandi') }}</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="form-control"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                @error('password_confirmation') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs" data-test="register-user-button">
                {{ __('Daftarkan Akun') }}
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-secondary">
            <span>{{ __('Sudah memiliki akun?') }}</span>
            <a href="{{ route('login') }}" class="text-success text-decoration-none font-semibold ms-1" wire:navigate>{{ __('Masuk') }}</a>
        </div>
    </div>
</x-layouts::auth>
