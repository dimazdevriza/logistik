@php
    $chapters = config('guide.chapters');
@endphp

@auth
<x-layouts::app.sidebar title="Panduan Sistem">
    @include('logistik.partials.guide-content', ['chapters' => $chapters])
</x-layouts::app.sidebar>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    @include('partials.head', ['title' => 'Panduan Sistem'])
    <script>
        try {
            var t = localStorage.getItem('theme');
            if (t !== 'light' && t !== 'dark') {
                t = 'dark';
            }
            document.documentElement.setAttribute('data-bs-theme', t);
        } catch (e) {}
    </script>
</head>
<body class="bg-body">
    @include('partials.icons')
    <div class="container py-3 py-md-4" x-data="{
        theme: document.documentElement.getAttribute('data-bs-theme'),
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', this.theme);
            try { localStorage.setItem('theme', this.theme); } catch (e) {}
        }
    }">
        <header class="card border shadow-sm rounded-4 mb-3 bg-body-tertiary">
            <div class="card-body py-2.5 px-3 d-flex align-items-center justify-content-between gap-2">
                <a href="{{ route('login') }}" class="d-inline-flex align-items-center text-decoration-none">
                    <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="img-fluid d-dark-none" style="height: 28px; object-fit: contain;" />
                    <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="img-fluid d-light-none" style="height: 28px; object-fit: contain;" />
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" @click="toggleTheme()" class="guide-control-btn" :aria-label="theme === 'dark' ? 'Ubah ke tampilan terang' : 'Ubah ke tampilan gelap'">
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true" x-show="theme === 'dark'"><use href="#i-sun"/></svg>
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true" x-show="theme === 'light'" x-cloak><use href="#i-moon"/></svg>
                        <span class="d-none d-sm-inline" x-text="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"></span>
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-success font-semibold d-inline-flex align-items-center gap-2" style="min-height: 44px;">
                        <span>Masuk ke Portal</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="main-card-container p-3 p-md-4">
            @include('logistik.partials.guide-content', ['chapters' => $chapters])
        </main>
    </div>
    @livewireScripts
</body>
</html>
@endauth
