<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-body-tertiary">
    <!-- Top Header Ribbon -->
    <div class="p-4 p-md-5 border-bottom bg-body-secondary bg-opacity-25 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 text-uppercase mb-2 font-geist small">Pengaturan Akun</span>
            <h2 class="display-6 fw-black text-body mb-1 font-outfit">
                Account <span class="text-success">Settings</span>
            </h2>
            <p class="text-secondary small mb-0">Kelola identitas profil, kredensial keamanan, otentikasi 2FA, dan preferensi tema.</p>
        </div>
        <div class="d-flex align-items-center gap-3 bg-body p-2.5 px-3 rounded-4 border shadow-xs">
            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold font-outfit shadow-sm" style="width: 40px; height: 40px; font-size: 16px;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <div class="fw-bold text-body small font-outfit lh-sm">{{ auth()->user()->name }}</div>
                <div class="extra-small text-secondary">{{ auth()->user()->email }}</div>
            </div>
        </div>
    </div>

    <!-- Main Settings Body Grid -->
    <div class="row g-0">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-md-4 border-end bg-body-secondary bg-opacity-10 p-3 p-lg-4">
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('profile.edit') }}" wire:navigate class="btn text-start d-flex align-items-center justify-content-between px-3.5 py-3 rounded-3 font-semibold transition-all {{ request()->routeIs('profile.edit') ? 'bg-success text-white shadow-xs border-success' : 'btn-outline-secondary border-0 text-body bg-transparent' }}">
                    <div class="d-flex align-items-center gap-3">
                        <svg width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                        </svg>
                        <span class="font-outfit fs-6">{{ __('Profil Akun') }}</span>
                    </div>
                    @if(request()->routeIs('profile.edit'))
                        <svg width="14" height="14" fill="currentColor" class="flex-shrink-0 ms-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                    @endif
                </a>

                <a href="{{ route('user-password.edit') }}" wire:navigate class="btn text-start d-flex align-items-center justify-content-between px-3.5 py-3 rounded-3 font-semibold transition-all {{ request()->routeIs('user-password.edit') ? 'bg-success text-white shadow-xs border-success' : 'btn-outline-secondary border-0 text-body bg-transparent' }}">
                    <div class="d-flex align-items-center gap-3">
                        <svg width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L12.5 8.707l-1.414 1.414a.5.5 0 0 1-.708 0L9 8.707 7.465 10A4 4 0 0 1 0 8m4-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                        </svg>
                        <span class="font-outfit fs-6">{{ __('Kata Sandi') }}</span>
                    </div>
                    @if(request()->routeIs('user-password.edit'))
                        <svg width="14" height="14" fill="currentColor" class="flex-shrink-0 ms-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                    @endif
                </a>

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <a href="{{ route('two-factor.show') }}" wire:navigate class="btn text-start d-flex align-items-center justify-content-between px-3.5 py-3 rounded-3 font-semibold transition-all {{ request()->routeIs('two-factor.show') ? 'bg-success text-white shadow-xs border-success' : 'btn-outline-secondary border-0 text-body bg-transparent' }}">
                        <div class="d-flex align-items-center gap-3">
                            <svg width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16">
                                <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233.45.45 0 0 0 .542 0a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.5 1.5 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 1.95 1.95 0 0 1-2.748 0 11.8 11.8 0 0 1-2.517-2.453C1.428 10.487.045 7.169.641 2.692A1.5 1.5 0 0 1 1.685 1.43c.658-.215 1.777-.57 2.887-.87z"/>
                                <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                            </svg>
                            <span class="font-outfit fs-6">{{ __('Keamanan 2FA') }}</span>
                        </div>
                        @if(request()->routeIs('two-factor.show'))
                            <svg width="14" height="14" fill="currentColor" class="flex-shrink-0 ms-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                        @endif
                    </a>
                @endif

                <a href="{{ route('appearance.edit') }}" wire:navigate class="btn text-start d-flex align-items-center justify-content-between px-3.5 py-3 rounded-3 font-semibold transition-all {{ request()->routeIs('appearance.edit') ? 'bg-success text-white shadow-xs border-success' : 'btn-outline-secondary border-0 text-body bg-transparent' }}">
                    <div class="d-flex align-items-center gap-3">
                        <svg width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            <path d="M16 8c0 3.15-1.866 5.986-4.72 7.253a.5.5 0 0 1-.644-.22 2.8 2.8 0 0 1-.136-.533 1.5 1.5 0 0 1 .632-1.393.5.5 0 0 0 .22-.441c0-.441-.358-.8-.8-.8H9.5a1.5 1.5 0 0 1-1.5-1.5c0-.184.033-.361.096-.525.048-.124.07-.255.07-.389 0-.441-.358-.8-.8-.8H6.268a.5.5 0 0 1-.468-.324A7.98 7.98 0 0 1 8 0c4.418 0 8 3.582 8 8M1.002 8a7 7 0 0 0 6.046 6.92 2.5 2.5 0 0 0 2.452-2.92.5.5 0 0 1 .494-.58h.508c1.27 0 2.3-1.03 2.3-2.3 0-3.308-2.692-6-6-6A7 7 0 0 0 1.002 8"/>
                        </svg>
                        <span class="font-outfit fs-6">{{ __('Tema Tampilan') }}</span>
                    </div>
                    @if(request()->routeIs('appearance.edit'))
                        <svg width="14" height="14" fill="currentColor" class="flex-shrink-0 ms-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                    @endif
                </a>
            </div>
        </div>

        <!-- Content Area Column -->
        <div class="col-lg-9 col-md-8 p-4 p-lg-5">
            <div class="pb-3 mb-4">
                <h4 class="fw-bold font-outfit text-body mb-1">{{ $heading ?? '' }}</h4>
                <p class="text-secondary small mb-0">{{ $subheading ?? '' }}</p>
            </div>

            <div class="w-100" style="max-width: 620px;">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
