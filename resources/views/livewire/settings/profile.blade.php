<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="vstack gap-3">
            <div>
                <label for="name" class="form-label font-semibold small">{{ __('Name') }}</label>
                <input id="name" wire:model="name" type="text" class="form-control rounded-3" required autocomplete="name" />
                @error('name') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="form-label font-semibold small">{{ __('Email') }}</label>
                <input id="email" wire:model="email" type="email" class="form-control rounded-3" required autocomplete="email" />
                @error('email') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-2">
                        <p class="small text-secondary mb-1">
                            {{ __('Your email address is unverified.') }}
                            <button type="button" class="btn btn-link text-success p-0 small text-decoration-none" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="small text-success font-semibold mb-0">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3 pt-2">
                <button type="submit" class="btn btn-success font-semibold px-4">{{ __('Save') }}</button>
                <x-action-message class="text-success small fw-semibold" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <hr class="my-4 border-secondary opacity-25" />
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
