<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        @include('partials.head')
        <style>
            .glass-container {
                position: relative;
                min-height: 100vh;
                background-color: var(--bs-body-bg);
                overflow: hidden;
            }
            .glass-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                opacity: 0.45;
                pointer-events: none;
            }
            .glass-orb-1 {
                top: -10%;
                left: -10%;
                width: 380px;
                height: 380px;
                background: #1F6F50;
            }
            .glass-orb-2 {
                bottom: -10%;
                right: -10%;
                width: 420px;
                height: 420px;
                background: #2E8B57;
            }
            .glass-orb-3 {
                top: 40%;
                right: 15%;
                width: 260px;
                height: 260px;
                background: #FFB703;
                opacity: 0.25;
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.45) !important;
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.5) !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            }
            [data-bs-theme="dark"] .glass-card {
                background: rgba(27, 27, 27, 0.6) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            }
            .glass-card .form-control {
                background: rgba(255, 255, 255, 0.5) !important;
                border-color: rgba(0, 0, 0, 0.1) !important;
                backdrop-filter: blur(4px);
            }
            [data-bs-theme="dark"] .glass-card .form-control {
                background: rgba(0, 0, 0, 0.3) !important;
                border-color: rgba(255, 255, 255, 0.15) !important;
            }
        </style>
    </head>
    <body class="glass-container d-flex flex-column align-items-center justify-content-center py-4 px-3">
        <!-- Ambient Glass Orbs -->
        <div class="glass-orb glass-orb-1"></div>
        <div class="glass-orb glass-orb-2"></div>
        <div class="glass-orb glass-orb-3"></div>

        <div class="w-100 position-relative z-1" style="max-width: 420px;">
            <div class="card glass-card rounded-4 p-4 p-sm-5">
                <div class="text-center mb-4">
                    <a href="{{ route('home') }}" class="d-inline-block text-decoration-none" wire:navigate>
                        <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="img-fluid d-dark-none mb-1" style="height: 52px; object-fit: contain;" />
                        <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="img-fluid d-light-none mb-1" style="height: 52px; object-fit: contain;" />
                    </a>
                </div>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
