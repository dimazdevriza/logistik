<x-layouts::auth :title="__('Konfirmasi Kata Sandi')">
    <div>
        <x-auth-header
            :title="__('Konfirmasi Kata Sandi')"
            :description="__('Ini adalah area aman aplikasi. Harap konfirmasi kata sandi Anda sebelum melanjutkan.')"
        />

        <x-auth-session-status class="text-center mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}">
            @csrf

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

            <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs" data-test="confirm-password-button">
                {{ __('Konfirmasi') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
