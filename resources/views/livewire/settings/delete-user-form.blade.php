<section class="mt-4">
    <div class="mb-3">
        <h5 class="fw-bold font-outfit text-danger mb-1">{{ __('Delete account') }}</h5>
        <p class="text-secondary small mb-0">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <button type="button" class="btn btn-outline-danger font-semibold btn-sm" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Delete account') }}
    </button>

    <div x-data="{ show: false }" x-on:open-modal.window="if ($event.detail === 'confirm-user-deletion') show = true" x-show="show" style="display: none;">
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <form method="POST" wire:submit="deleteUser">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title font-outfit fw-bold text-danger">{{ __('Are you sure you want to delete your account?') }}</h5>
                            <button type="button" class="btn-close" @click="show = false"></button>
                        </div>
                        <div class="modal-body py-4">
                            <p class="text-secondary small mb-3">
                                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                            </p>
                            <div>
                                <label for="del-password" class="form-label font-semibold small">{{ __('Password') }}</label>
                                <input id="del-password" wire:model="password" type="password" class="form-control rounded-3" />
                                @error('password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary font-semibold" @click="show = false">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-danger font-semibold">{{ __('Delete account') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
