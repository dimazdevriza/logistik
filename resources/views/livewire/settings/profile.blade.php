<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profil Pengguna')" :subheading="__('Perbarui nama lengkap dan alamat email yang terdaftar pada akun Anda.')">
        <form wire:submit="updateProfileInformation" class="vstack gap-4">

            <div>
                <label for="name" class="form-label font-semibold small text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-body border-end-0 text-secondary">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                    </span>
                    <input id="name" wire:model="name" type="text" class="form-control border-start-0" placeholder="Masukkan nama lengkap..." required autocomplete="name" />
                </div>
                @error('name') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="form-label font-semibold small text-secondary">Alamat Email <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-body border-end-0 text-secondary">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg>
                    </span>
                    <input id="email" wire:model="email" type="email" class="form-control border-start-0" placeholder="nama@email.com" required autocomplete="email" />
                </div>
                @error('email') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-2 p-3 rounded-3 bg-warning-subtle text-warning border border-warning-subtle small">
                        <p class="mb-1 fw-semibold">
                            {{ __('Alamat email Anda belum diverifikasi.') }}
                        </p>
                        <button type="button" class="btn btn-link text-warning p-0 small text-decoration-underline fw-bold" wire:click.prevent="resendVerificationNotification">
                            {{ __('Kirim ulang email verifikasi') }}
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="small text-success font-semibold mt-2 mb-0">
                                {{ __('Tautan verifikasi baru telah dikirim ke email Anda.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3 pt-2">
                <button type="submit" class="btn btn-success font-semibold px-4 py-2 rounded-3 shadow-xs d-inline-flex align-items-center gap-2">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                    <span>Simpan Perubahan</span>
                </button>
                <x-action-message class="text-success small fw-semibold" on="profile-updated">
                    {{ __('Perubahan berhasil disimpan.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <hr class="my-5 border-secondary opacity-25" />
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
