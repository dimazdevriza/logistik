<x-layouts::auth :title="__('Atur Ulang Kata Sandi')">
    <div>
        <x-auth-header :title="__('Atur Ulang Kata Sandi')" :description="__('Silakan masukkan kata sandi baru Anda di bawah ini')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label font-semibold small text-body">{{ __('Alamat Email') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="form-control"
                    value="{{ request('email') }}"
                    required
                    autocomplete="email"
                />
                @error('email') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label font-semibold small text-body">{{ __('Kata Sandi Baru') }}</label>
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
                <label for="password_confirmation" class="form-label font-semibold small text-body">{{ __('Konfirmasi Kata Sandi Baru') }}</label>
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

            <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs" data-test="reset-password-button">
                {{ __('Simpan Kata Sandi Baru') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
