<x-layouts::auth :title="__('Otentikasi Dua-Faktor')">
    <div
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;
                this.code = '';
                this.recovery_code = '';
            },
        }"
    >
        <div x-show="!showRecoveryInput">
            <x-auth-header
                :title="__('Kode Otentikator')"
                :description="__('Masukkan 6-digit kode otentikasi dari aplikasi Google Authenticator Anda.')"
            />
        </div>

        <div x-show="showRecoveryInput">
            <x-auth-header
                :title="__('Kode Pemulihan Darurat')"
                :description="__('Masukkan salah satu kode pemulihan darurat Anda untuk mengakses akun.')"
            />
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf

            <div class="vstack gap-3 text-center my-3">
                <div x-show="!showRecoveryInput">
                    <div class="mb-3">
                        <input
                            type="text"
                            name="code"
                            inputmode="numeric"
                            maxlength="6"
                            class="form-control form-control-lg text-center font-mono tracking-widest rounded-3"
                            placeholder="123456"
                            x-model="code"
                            autofocus
                        />
                        @error('code') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div x-show="showRecoveryInput">
                    <div class="mb-3">
                        <input
                            type="text"
                            name="recovery_code"
                            x-ref="recovery_code"
                            class="form-control font-mono rounded-3"
                            placeholder="xxxx-xxxx-xxxx"
                            x-model="recovery_code"
                            x-bind:required="showRecoveryInput"
                        />
                        @error('recovery_code') <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs">
                    {{ __('Masuk ke Portal') }}
                </button>
            </div>

            <div class="mt-4 pt-3 border-top text-center small text-secondary">
                <span>{{ __('atau Anda dapat') }}</span>
                <button type="button" class="btn btn-link text-success text-decoration-none font-semibold p-0 ms-1 extra-small" @click="toggleInput()">
                    <span x-show="!showRecoveryInput">{{ __('Gunakan Kode Pemulihan') }}</span>
                    <span x-show="showRecoveryInput">{{ __('Gunakan Kode Authenticator') }}</span>
                </button>
            </div>
        </form>
    </div>
</x-layouts::auth>
