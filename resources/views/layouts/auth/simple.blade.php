<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-light min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="container" style="max-width: 420px;">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="text-decoration-none" wire:navigate>
                    <h2 class="fw-black text-success font-outfit">D'Royal Village</h2>
                </a>
            </div>
            <div class="card border-0 shadow-lg rounded-4 p-4 bg-body">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
