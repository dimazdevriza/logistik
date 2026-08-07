<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="vstack gap-3">
            <div>
                <label for="current_password" class="form-label font-semibold small">{{ __('Current password') }}</label>
                <input id="current_password" wire:model="current_password" type="password" class="form-control rounded-3" required autocomplete="current-password" />
                @error('current_password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="form-label font-semibold small">{{ __('New password') }}</label>
                <input id="password" wire:model="password" type="password" class="form-control rounded-3" required autocomplete="new-password" />
                @error('password') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="form-label font-semibold small">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" wire:model="password_confirmation" type="password" class="form-control rounded-3" required autocomplete="new-password" />
                @error('password_confirmation') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div class="d-flex align-items-center gap-3 pt-2">
                <button type="submit" class="btn btn-success font-semibold px-4">{{ __('Save') }}</button>
                <x-action-message class="text-success small fw-semibold" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
