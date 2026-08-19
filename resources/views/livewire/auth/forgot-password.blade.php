<x-layouts::auth :title="__('Lupa Kata Sandi')">
    <div>
        <x-auth-header :title="__('Lupa Kata Sandi')" :description="__('Masukkan alamat email Anda untuk menerima tautan pemulihan kata sandi')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
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

            <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs" data-test="email-password-reset-link-button">
                {{ __('Kirim Tautan Pemulihan') }}
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-secondary">
            <span>{{ __('Atau kembali ke halaman') }}</span>
            <a href="{{ route('login') }}" class="text-success text-decoration-none font-semibold ms-1" wire:navigate>{{ __('Masuk') }}</a>
        </div>
    </div>
</x-layouts::auth>
