<section class="mt-2">
    <div class="card border border-danger-subtle bg-danger-subtle bg-opacity-25 rounded-4 p-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger p-1" style="width: 24px; height: 24px;">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/><path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/></svg>
                    </span>
                    <h5 class="fw-bold font-outfit text-danger mb-0">{{ __('Hapus Akun') }}</h5>
                </div>
                <p class="text-secondary small mb-0">{{ __('Tindakan permanen: semua data & akses akun Anda akan dihapus selamanya.') }}</p>
            </div>

            <button type="button" class="btn btn-outline-danger font-semibold btn-sm px-3 py-2 rounded-3 text-nowrap" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
                {{ __('Hapus Akun Ini') }}
            </button>
        </div>
    </div>

    <div x-data="{ show: false }" x-on:open-modal.window="if ($event.detail === 'confirm-user-deletion') show = true" x-show="show" style="display: none;">
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <form method="POST" wire:submit="deleteUser">
                        <div class="modal-header border-bottom py-3 px-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger p-2" style="width: 32px; height: 32px;">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                </span>
                                <h5 class="modal-title font-outfit fw-bold text-danger mb-0">{{ __('Konfirmasi Hapus Akun') }}</h5>
                            </div>
                            <button type="button" class="btn-close" @click="show = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="text-secondary small mb-3">
                                {{ __('Setelah akun Anda dihapus, semua data dan hak akses akan dihapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi tindakan ini.') }}
                            </p>
                            <div>
                                <label for="del-password" class="form-label font-semibold small text-secondary">{{ __('Kata Sandi Konfirmasi') }}</label>
                                <input id="del-password" wire:model="password" type="password" class="form-control rounded-3" placeholder="Masukkan kata sandi akun Anda..." />
                                @error('password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4 py-3 px-4">
                            <button type="button" class="btn btn-outline-secondary font-semibold" @click="show = false">{{ __('Batal') }}</button>
                            <button type="submit" class="btn btn-danger font-semibold">{{ __('Ya, Hapus Akun') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
