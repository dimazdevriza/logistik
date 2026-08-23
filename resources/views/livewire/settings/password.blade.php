<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Perbarui Kata Sandi')" :subheading="__('Pastikan akun Anda menggunakan kata sandi yang panjang dan acak demi keamanan.')">
        <form method="POST" wire:submit="updatePassword" class="vstack gap-4">
            <div>
                <label for="current_password" class="form-label font-semibold small text-secondary">{{ __('Kata Sandi Saat Ini') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-body border-end-0 text-secondary">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L12.5 8.707l-1.414 1.414a.5.5 0 0 1-.708 0L9 8.707 7.465 10A4 4 0 0 1 0 8m4-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/></svg>
                    </span>
                    <input id="current_password" wire:model="current_password" type="password" class="form-control border-start-0" placeholder="Masukkan kata sandi lama..." required autocomplete="current-password" />
                </div>
                @error('current_password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="form-label font-semibold small text-secondary">{{ __('Kata Sandi Baru') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-body border-end-0 text-secondary">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                    </span>
                    <input id="password" wire:model="password" type="password" class="form-control border-start-0" placeholder="Minimal 8 karakter..." required autocomplete="new-password" />
                </div>
                @error('password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="form-label font-semibold small text-secondary">{{ __('Konfirmasi Kata Sandi Baru') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-body border-end-0 text-secondary">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                    </span>
                    <input id="password_confirmation" wire:model="password_confirmation" type="password" class="form-control border-start-0" placeholder="Ulangi kata sandi baru..." required autocomplete="new-password" />
                </div>
                @error('password_confirmation') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div class="d-flex align-items-center gap-3 pt-2">
                <button type="submit" class="btn btn-success font-semibold px-4 py-2 rounded-3 shadow-xs d-inline-flex align-items-center gap-2">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                    <span>Simpan Kata Sandi</span>
                </button>
                <x-action-message class="text-success small fw-semibold" on="password-updated">
                    {{ __('Kata sandi berhasil diperbarui.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
