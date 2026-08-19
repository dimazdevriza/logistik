<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Otentikasi Dua-Faktor (Google Authenticator 2FA)')"
        :subheading="__('Tingkatkan keamanan akun Anda menggunakan aplikasi otentikator seperti Google Authenticator')"
    >
        <div class="vstack gap-4" wire:cloak>
            @if ($twoFactorEnabled)
                <div class="card bg-success-subtle border-success-subtle rounded-3 p-4">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-success text-white px-3 py-2 rounded-pill font-geist small">Aktif</span>
                        <h6 class="fw-bold text-success mb-0">Google Authenticator 2FA Terhubung</h6>
                    </div>
                    <p class="text-secondary small mb-0">
                        Otentikasi dua faktor saat ini aktif pada akun Anda. Setiap kali masuk, Anda akan diminta memasukkan 6-digit kode verifikasi dari aplikasi Google Authenticator.
                    </p>
                </div>

                <div class="card border rounded-3 p-4 bg-body">
                    <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>
                </div>

                <div class="pt-2">
                    <button
                        type="button"
                        class="btn btn-outline-danger font-semibold px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2"
                        wire:click="disable"
                    >
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233.45.45 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.5 1.5 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 1.95 1.95 0 0 1-2.748 0 11.8 11.8 0 0 1-2.517-2.453C1.428 10.487.045 7.169.641 2.692A1.5 1.5 0 0 1 1.685 1.43c.658-.215 1.777-.57 2.887-.87z"/>
                        </svg>
                        <span>Nonaktifkan 2FA</span>
                    </button>
                </div>
            @else
                <div class="card bg-body-tertiary border rounded-3 p-4">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-secondary text-white px-3 py-2 rounded-pill font-geist small">Nonaktif</span>
                        <h6 class="fw-bold text-body mb-0">Google Authenticator Belum Dikonfigurasi</h6>
                    </div>
                    <p class="text-secondary small mb-3">
                        Aktifkan fitur 2FA untuk mengamankan akun Anda dari akses yang tidak sah. Anda dapat menggunakan Google Authenticator, Authy, atau aplikasi TOTP pilihan Anda.
                    </p>

                    <div>
                        <button
                            type="button"
                            class="btn btn-success font-semibold px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2"
                            wire:click="enable"
                        >
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                            </svg>
                            <span>Aktifkan Google 2FA</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </x-settings.layout>

    <!-- Bootstrap 5 Setup Modal Backdrop -->
    @if ($showModal)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.6);" tabindex="-1" wire:cloak>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 p-3 bg-body">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title font-outfit fw-bold text-body mb-1">
                                {{ $showVerificationStep ? __('Verifikasi Kode Authenticator') : __('Penyiapan Google Authenticator') }}
                            </h5>
                            <p class="text-secondary extra-small mb-0">
                                {{ $showVerificationStep ? __('Masukkan 6-digit kode OTP dari aplikasi Anda') : __('Pindai kode QR di bawah ini menggunakan aplikasi Google Authenticator') }}
                            </p>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body py-4">
                        @if (! $showVerificationStep)
                            <!-- Step 1: Scan QR Code -->
                            <div class="text-center vstack gap-3">
                                <div class="p-3 bg-white border rounded-4 d-inline-block mx-auto shadow-xs">
                                    {!! $qrCodeSvg !!}
                                </div>

                                <div class="card bg-body-tertiary border rounded-3 p-3 text-start">
                                    <div class="extra-small text-secondary fw-semibold text-uppercase tracking-wider mb-1">Kunci Penyiapan Manual:</div>
                                    <div class="font-mono small fw-bold text-body select-all text-break">{{ $manualSetupKey }}</div>
                                </div>
                            </div>
                        @else
                            <!-- Step 2: Verification Code -->
                            <form wire:submit="confirmTwoFactor" class="vstack gap-3">
                                <div>
                                    <label for="two-factor-code" class="form-label font-semibold small">{{ __('Kode Verifikasi (6-digit)') }}</label>
                                    <input
                                        id="two-factor-code"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="6"
                                        class="form-control form-control-lg text-center font-mono tracking-widest rounded-3"
                                        placeholder="123456"
                                        wire:model="code"
                                        autofocus
                                    />
                                    @error('code')
                                        <span class="text-danger extra-small mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </form>
                        @endif
                    </div>

                    <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                        @if ($showVerificationStep)
                            <button type="button" class="btn btn-outline-secondary font-semibold px-3 py-2 rounded-3" wire:click="resetVerification">
                                {{ __('Kembali') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-secondary font-semibold px-3 py-2 rounded-3" wire:click="closeModal">
                                {{ __('Batal') }}
                            </button>
                        @endif

                        @if (! $showVerificationStep)
                            <button type="button" class="btn btn-success font-semibold px-4 py-2 rounded-3" wire:click="showVerificationIfNecessary">
                                {{ __('Lanjut ke Verifikasi') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-success font-semibold px-4 py-2 rounded-3" wire:click="confirmTwoFactor">
                                {{ __('Konfirmasi & Simpan') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
