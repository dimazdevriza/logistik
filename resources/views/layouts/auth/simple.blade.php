<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        @include('partials.head')
        <script>
            try {
                const theme = localStorage.getItem('theme');
                document.documentElement.setAttribute('data-bs-theme', theme === 'light' ? 'light' : 'dark');
            } catch (e) {}
        </script>
    </head>
    <body class="auth-shell d-flex flex-column align-items-center justify-content-center py-4 px-3">
        <div class="auth-frame w-100">
            <div class="auth-card">
                <div class="auth-brand text-center">
                    <a href="{{ route('home') }}" class="d-inline-block text-decoration-none" wire:navigate>
                        <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="img-fluid d-dark-none" />
                        <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="img-fluid d-light-none" />
                    </a>
                    <p class="auth-kicker mb-0">Portal logistik pembangunan</p>
                </div>

                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>
