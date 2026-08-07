@php
    $user = auth()->user();
    $role = $user?->role;
    $isStaff = in_array($role, ['logistik', 'admin'], true);

    // Nav model: each section is a label plus its links. Sections with no
    // visible links are dropped, so role changes never leave an empty header.
    $sections = collect([
        [
            'label' => 'Ringkasan',
            'links' => [
                ['route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'i-dashboard', 'label' => 'Dashboard'],
            ],
        ],
        [
            'label' => 'Inventaris',
            'show' => $isStaff,
            'links' => [
                ['route' => 'logistik.materials', 'active' => 'logistik.materials', 'icon' => 'i-box', 'label' => 'Material'],
                ['route' => 'logistik.tools', 'active' => 'logistik.tools', 'icon' => 'i-wrench', 'label' => 'Alat'],
                ['route' => 'logistik.suppliers', 'active' => 'logistik.suppliers', 'icon' => 'i-truck', 'label' => 'Supplier'],
                ['route' => 'logistik.categories', 'active' => 'logistik.categories', 'icon' => 'i-tags', 'label' => 'Kategori'],
            ],
        ],
        [
            'label' => 'Lapangan',
            'show' => $isStaff,
            'links' => [
                ['route' => 'logistik.houses', 'active' => 'logistik.houses*', 'icon' => 'i-houses', 'label' => 'Rumah'],
                ['route' => 'logistik.transaksi', 'active' => 'logistik.transaksi', 'icon' => 'i-transfer', 'label' => 'Transaksi'],
            ],
        ],
        [
            'label' => 'Riwayat',
            'show' => $isStaff,
            'links' => [
                ['route' => 'logistik.material-log', 'active' => 'logistik.material-log', 'icon' => 'i-journal-text', 'label' => 'Catatan Material'],
                ['route' => 'logistik.tool-log', 'active' => 'logistik.tool-log', 'icon' => 'i-journal-check', 'label' => 'Catatan Alat'],
            ],
        ],
        [
            'label' => 'Admin',
            'show' => $role === 'admin',
            'links' => [
                ['route' => 'admin.house-costs', 'active' => 'admin.house-costs*', 'icon' => 'i-chart', 'label' => 'Biaya Rumah'],
                ['route' => 'admin.users', 'active' => 'admin.users', 'icon' => 'i-people', 'label' => 'Manajemen User'],
            ],
        ],
    ])->filter(fn ($s) => $s['show'] ?? true)->values();

    $currentLabel = collect($sections)->flatMap(fn ($s) => $s['links'])
        ->first(fn ($l) => request()->routeIs($l['active']))['label'] ?? null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    @include('partials.head')
    <script>
        // Applied before first paint so the theme never flashes.
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t !== 'light' && t !== 'dark') {
                    t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>
</head>
<body class="bg-body">
    @include('partials.icons')

    <div
        class="app-shell d-flex"
        x-data="{
            rail: false,
            open: false,
            init() {
                try { this.rail = localStorage.getItem('sidebar-rail') === '1' } catch (e) {}
                this.$watch('rail', v => { try { localStorage.setItem('sidebar-rail', v ? '1' : '0') } catch (e) {} })
            },
            theme: document.documentElement.getAttribute('data-bs-theme'),
            toggleTheme() {
                this.theme = this.theme === 'dark' ? 'light' : 'dark'
                document.documentElement.setAttribute('data-bs-theme', this.theme)
                try { localStorage.setItem('theme', this.theme) } catch (e) {}
            },
        }"
        :class="{ 'is-rail': rail, 'is-open': open }"
        @keydown.escape.window="open = false"
    >
        <div class="sidebar-backdrop" @click="open = false" aria-hidden="true"></div>

        <aside class="sidebar" aria-label="Navigasi utama">
            <!-- Brand -->
            <div class="d-flex align-items-center gap-2 p-3">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none flex-grow-1 overflow-hidden">
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-success text-white flex-shrink-0" style="width: 34px; height: 34px;">
                        <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-logo"/></svg>
                    </span>
                    <span class="sidebar-label lh-sm overflow-hidden">
                        <span class="d-block fw-bold text-body text-truncate" style="letter-spacing: -0.02em;">D'Royal</span>
                        <span class="d-block extra-small text-secondary text-truncate font-geist" style="letter-spacing: 0.08em;">LOGISTIK</span>
                    </span>
                </a>

                <!-- Desktop rail toggle -->
                <button
                    type="button"
                    class="btn btn-sm btn-link text-secondary p-1 d-none d-lg-inline-flex rail-hide"
                    @click="rail = !rail"
                    aria-label="Ciutkan menu"
                >
                    <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-chevron-left"/></svg>
                </button>
            </div>

            <!-- Expand button, rail mode only -->
            <div class="px-3 pb-1 d-none d-lg-block" x-show="rail" x-cloak>
                <button type="button" class="btn btn-sm btn-link text-secondary w-100 p-1" @click="rail = false" aria-label="Lebarkan menu">
                    <svg width="14" height="14" fill="currentColor" style="transform: rotate(180deg);" aria-hidden="true"><use href="#i-chevron-left"/></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow-1 overflow-y-auto overflow-x-hidden px-3 pb-2">
                @foreach ($sections as $section)
                    <div class="sidebar-section">{{ $section['label'] }}</div>
                    @foreach ($section['links'] as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            class="sidebar-link {{ request()->routeIs($link['active']) ? 'active' : '' }}"
                            data-label="{{ $link['label'] }}"
                            @if (request()->routeIs($link['active'])) aria-current="page" @endif
                            @click="open = false"
                        >
                            <svg width="17" height="17" fill="currentColor" aria-hidden="true"><use href="#{{ $link['icon'] }}"/></svg>
                            <span class="sidebar-label">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <!-- Account -->
            <div class="border-top p-2 mt-auto">
                <div class="dropdown dropup">
                    <button
                        type="button"
                        class="btn w-100 d-flex align-items-center gap-2 p-2 border-0 text-start user-profile-card"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        aria-expanded="false"
                    >
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success fw-bold flex-shrink-0 extra-small" style="width: 32px; height: 32px;">
                            {{ $user?->initials() }}
                        </span>
                        <span class="sidebar-label overflow-hidden lh-sm flex-grow-1">
                            <span class="d-block small fw-semibold text-body text-truncate">{{ $user?->name }}</span>
                            <span class="d-block extra-small text-secondary text-truncate">{{ ucfirst($role ?? '') }}</span>
                        </span>
                    </button>

                    <ul class="dropdown-menu shadow-lg border rounded-3 p-1 w-100">
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 small fw-medium" href="{{ route('profile.edit') }}">
                                <svg width="14" height="14" fill="currentColor" class="text-secondary" aria-hidden="true"><use href="#i-gear"/></svg>
                                Pengaturan
                            </a>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 small fw-medium" @click="toggleTheme()">
                                <svg width="14" height="14" fill="currentColor" class="text-secondary" aria-hidden="true">
                                    <use :href="theme === 'dark' ? '#i-sun' : '#i-moon'" href="#i-moon"/>
                                </svg>
                                <span x-text="theme === 'dark' ? 'Mode terang' : 'Mode gelap'">Mode gelap</span>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 small fw-medium text-danger">
                                    <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-logout"/></svg>
                                    Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <div class="flex-grow-1 min-vh-100 d-flex flex-column overflow-hidden">
            <!-- Mobile top bar -->
            <header class="d-lg-none sticky-top d-flex align-items-center gap-2 px-3 py-2 bg-body border-bottom">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center p-2" @click="open = true" aria-label="Buka menu">
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-menu"/></svg>
                </button>
                <span class="fw-semibold text-body text-truncate">{{ $title ?? $currentLabel ?? "D'Royal Logistik" }}</span>
            </header>

            <main class="flex-grow-1 p-3 p-md-4">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
