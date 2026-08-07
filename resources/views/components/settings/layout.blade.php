<div class="row g-4">
    <div class="col-md-3">
        <div class="list-group list-group-flush border rounded-4 overflow-hidden shadow-xs bg-body-tertiary">
            <a href="{{ route('profile.edit') }}" wire:navigate class="list-group-item list-group-item-action py-3 px-3 fw-semibold d-flex align-items-center gap-2 {{ request()->routeIs('profile.edit') ? 'active bg-success border-success text-white' : 'text-body' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                </svg>
                {{ __('Profile') }}
            </a>
            <a href="{{ route('user-password.edit') }}" wire:navigate class="list-group-item list-group-item-action py-3 px-3 fw-semibold d-flex align-items-center gap-2 {{ request()->routeIs('user-password.edit') ? 'active bg-success border-success text-white' : 'text-body' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16">
                    <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L12.5 8.707l-1.414 1.414a.5.5 0 0 1-.708 0L9 8.707 7.465 10A4 4 0 0 1 0 8m4-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                </svg>
                {{ __('Password') }}
            </a>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <a href="{{ route('two-factor.show') }}" wire:navigate class="list-group-item list-group-item-action py-3 px-3 fw-semibold d-flex align-items-center gap-2 {{ request()->routeIs('two-factor.show') ? 'active bg-success border-success text-white' : 'text-body' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
                        <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233.45.45 0 0 0 .542 0a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.5 1.5 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 1.95 1.95 0 0 1-2.748 0 11.8 11.8 0 0 1-2.517-2.453C1.428 10.487.045 7.169.641 2.692A1.5 1.5 0 0 1 1.685 1.43c.658-.215 1.777-.57 2.887-.87z"/>
                        <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                    </svg>
                    {{ __('Two-factor auth') }}
                </a>
            @endif
            <a href="{{ route('appearance.edit') }}" wire:navigate class="list-group-item list-group-item-action py-3 px-3 fw-semibold d-flex align-items-center gap-2 {{ request()->routeIs('appearance.edit') ? 'active bg-success border-success text-white' : 'text-body' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-palette" viewBox="0 0 16 16">
                    <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                    <path d="M16 8c0 3.15-1.866 5.986-4.72 7.253a.5.5 0 0 1-.644-.22 2.8 2.8 0 0 1-.136-.533 1.5 1.5 0 0 1 .632-1.393.5.5 0 0 0 .22-.441c0-.441-.358-.8-.8-.8H9.5a1.5 1.5 0 0 1-1.5-1.5c0-.184.033-.361.096-.525.048-.124.07-.255.07-.389 0-.441-.358-.8-.8-.8H6.268a.5.5 0 0 1-.468-.324A7.98 7.98 0 0 1 8 0c4.418 0 8 3.582 8 8M1.002 8a7 7 0 0 0 6.046 6.92 2.5 2.5 0 0 0 2.452-2.92.5.5 0 0 1 .494-.58h.508c1.27 0 2.3-1.03 2.3-2.3 0-3.308-2.692-6-6-6A7 7 0 0 0 1.002 8"/>
                </svg>
                {{ __('Appearance') }}
            </a>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
            <h4 class="fw-bold font-outfit text-body mb-1">{{ $heading ?? '' }}</h4>
            <p class="text-secondary small mb-4">{{ $subheading ?? '' }}</p>

            <div class="w-100 max-w-lg">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
