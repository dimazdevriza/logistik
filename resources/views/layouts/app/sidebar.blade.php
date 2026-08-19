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
            'links' => array_filter([
                $isStaff ? ['route' => 'logistik.houses', 'active' => 'logistik.houses*', 'icon' => 'i-houses', 'label' => 'Rumah'] : null,
                $isStaff ? ['route' => 'logistik.transaksi', 'active' => 'logistik.transaksi', 'icon' => 'i-transfer', 'label' => 'Transaksi Material & Alat'] : null,
            ]),
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
        // Applied before first paint so the theme and sidebar rail state never flash.
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t !== 'light' && t !== 'dark') {
                    t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', t);

                if (localStorage.getItem('sidebar-rail') === '1') {
                    document.documentElement.classList.add('is-rail-initial');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="bg-body">
    @include('partials.icons')

    <div
        class="app-shell d-flex"
        x-data="{
            rail: (function() { try { return localStorage.getItem('sidebar-rail') === '1' } catch(e) { return false } })(),
            open: false,
            mobileOpen: false,
            init() {
                this.$watch('rail', v => {
                    try { localStorage.setItem('sidebar-rail', v ? '1' : '0') } catch (e) {}
                    if (v) { document.documentElement.classList.add('is-rail-initial'); }
                    else { document.documentElement.classList.remove('is-rail-initial'); }
                })
            },
            theme: document.documentElement.getAttribute('data-bs-theme'),
            toggleTheme() {
                this.theme = this.theme === 'dark' ? 'light' : 'dark'
                document.documentElement.setAttribute('data-bs-theme', this.theme)
                try { localStorage.setItem('theme', this.theme) } catch (e) {}
            },
        }"
        :class="{ 'is-rail': rail, 'is-open': open }"
        @keydown.escape.window="open = false; mobileOpen = false;"
    >
        <div class="sidebar-backdrop" @click="open = false" aria-hidden="true"></div>

        <aside class="sidebar" aria-label="Navigasi utama">
            <!-- Brand -->
            <div class="app-header d-flex align-items-center justify-content-between px-3 border-bottom overflow-hidden" @click="if (rail) rail = false">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none overflow-hidden my-auto" @click="if (rail) { $event.preventDefault(); }">
                    <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="sidebar-label img-fluid d-dark-none" style="height: 32px; object-fit: contain;" />
                    <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="sidebar-label img-fluid d-light-none" style="height: 32px; object-fit: contain;" />
                    <span class="d-none rail-show align-items-center justify-content-center fw-bold text-success font-outfit fs-5 lh-1">
                        D'R
                    </span>
                </a>

                <!-- Desktop rail toggle button -->
                <button
                    type="button"
                    class="btn btn-sm btn-icon text-secondary p-1.5 d-none d-lg-inline-flex align-items-center justify-content-center rounded-2 hover-bg flex-shrink-0 ms-1"
                    @click.stop="rail = !rail"
                    :aria-label="rail ? 'Lebarkan menu' : 'Ciutkan menu'"
                    :title="rail ? 'Lebarkan menu' : 'Ciutkan menu'"
                >
                    <svg width="14" height="14" fill="currentColor" :style="rail ? 'transform: rotate(180deg);' : ''" aria-hidden="true">
                        <use href="#i-chevron-left"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav 
                class="flex-grow-1 overflow-y-auto overflow-x-hidden px-3 pb-2 scroll-fade" 
                style="overscroll-behavior: contain;"
                x-data="{ isScrolling: false, scrollTimer: null }"
                :class="{ 'is-scrolling': isScrolling }"
                @scroll="
                    isScrolling = true;
                    clearTimeout(scrollTimer);
                    scrollTimer = setTimeout(() => { isScrolling = false; }, 800);
                "
            >
                @foreach ($sections as $section)
                    <div class="sidebar-section">
                        <span class="sidebar-label">{{ $section['label'] }}</span>
                    </div>
                    @foreach ($section['links'] as $link)
                        @php $isActive = request()->routeIs($link['active']); @endphp
                        <a
                            href="{{ route($link['route']) }}"
                            class="sidebar-link {{ $isActive ? 'active' : '' }}"
                            data-label="{{ $link['label'] }}"
                            :title="rail ? '{{ $link['label'] }}' : ''"
                            @if ($isActive) aria-current="page" @endif
                            @click="open = false"
                        >
                            <svg width="17" height="17" fill="currentColor" aria-hidden="true"><use href="#{{ $link['icon'] }}"/></svg>
                            <span class="sidebar-label">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <!-- Account Footer -->
            <div class="border-top p-2 mt-auto">
                <div class="d-flex align-items-center justify-content-between user-profile-card p-1 rounded-3">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success fw-bold flex-shrink-0 extra-small" style="width: 32px; height: 32px;">
                            {{ $user?->initials() }}
                        </span>
                        <span class="sidebar-label overflow-hidden lh-sm">
                            <span class="d-block small fw-semibold text-body text-truncate">{{ $user?->name }}</span>
                            <span class="d-block extra-small text-secondary text-truncate">{{ ucfirst($role ?? '') }}</span>
                        </span>
                    </div>

                    <!-- Action buttons: Settings & Logout on far right -->
                    <div class="d-flex align-items-center gap-1 sidebar-label flex-shrink-0">
                        <a href="{{ route('profile.edit') }}" class="btn btn-icon text-secondary p-0 rounded-2 hover-bg d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Pengaturan" aria-label="Pengaturan">
                            <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-gear"/></svg>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="m-0 d-inline-flex align-items-center">
                            @csrf
                            <button type="submit" class="btn btn-icon text-danger p-0 rounded-2 hover-bg d-inline-flex align-items-center justify-content-center border-0 bg-transparent" style="width: 32px; height: 32px;" title="Keluar" aria-label="Keluar">
                                <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-logout"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-content min-vh-100 d-flex flex-column">
            <!-- Mobile top bar with expandable dropdown accordion -->
            <header class="app-header-mobile d-lg-none sticky-top border shadow-sm rounded-4 overflow-hidden mb-3" style="top: 0.5rem; z-index: 1020; transition: max-height 0.35s cubic-bezier(0.16, 1, 0.3, 1);">
                <div class="d-flex align-items-center justify-content-between px-3" style="min-height: 56px;">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <button type="button" class="btn btn-icon text-body p-2 d-inline-flex align-items-center justify-content-center rounded-3 hover-bg border-0 bg-transparent" @click="mobileOpen = !mobileOpen" aria-label="Buka menu">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true" :style="mobileOpen ? 'transform: rotate(90deg); transition: transform 0.2s ease;' : 'transition: transform 0.2s ease;'"><use href="#i-menu"/></svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none ms-1">
                            <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="img-fluid d-dark-none" style="height: 24px; object-fit: contain;" />
                            <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="img-fluid d-light-none" style="height: 24px; object-fit: contain;" />
                        </a>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle font-outfit fw-bold px-2.5 py-1.5 rounded-pill extra-small">
                            {{ $title ?? "D'Royal Logistik" }}
                        </span>
                        <button type="button" class="btn p-0 border-0" @click="mobileOpen = !mobileOpen">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success fw-bold extra-small" style="width: 32px; height: 32px;">
                                {{ $user?->initials() }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Smoothly expanding navigation accordion content -->
                <div 
                    x-show="mobileOpen" 
                    x-collapse
                    class="border-top px-3 py-3 overflow-y-auto"
                    style="max-height: calc(80vh - 56px);"
                >
                    <nav class="d-flex flex-column gap-1">
                        @foreach ($sections as $section)
                            <div class="sidebar-section px-2 mt-2 mb-1 text-uppercase font-geist fw-bold extra-small text-secondary">
                                {{ $section['label'] }}
                            </div>
                            @foreach ($section['links'] as $link)
                                @php $isActive = request()->routeIs($link['active']); @endphp
                                <a 
                                    href="{{ route($link['route']) }}" 
                                    class="sidebar-link d-flex align-items-center gap-3 px-3 py-2 rounded-3 text-decoration-none {{ $isActive ? 'active bg-success-subtle text-success fw-bold' : 'text-body hover-bg' }}"
                                    @click="mobileOpen = false"
                                >
                                    <svg width="18" height="18" fill="currentColor"><use href="#{{ $link['icon'] }}"/></svg>
                                    <span class="small">{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        @endforeach
                    </nav>

                    <div class="border-top mt-3 pt-3 d-flex align-items-center justify-content-between px-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success fw-bold extra-small" style="width: 32px; height: 32px;">
                                {{ $user?->initials() }}
                            </span>
                            <div class="lh-sm">
                                <div class="fw-bold small text-body">{{ $user?->name }}</div>
                                <div class="extra-small text-secondary text-capitalize">{{ $user?->role }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('profile.edit') }}" class="btn btn-icon text-secondary p-1.5 rounded-2 hover-bg" title="Pengaturan">
                                <svg width="16" height="16" fill="currentColor"><use href="#i-gear"/></svg>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-icon text-danger p-1.5 rounded-2 hover-bg border-0 bg-transparent" title="Keluar">
                                    <svg width="16" height="16" fill="currentColor"><use href="#i-logout"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-grow-1 p-0 d-flex flex-column overflow-hidden">
                <div 
                    class="main-card-container p-4 flex-grow-1 scroll-fade"
                    x-data="{ isScrolling: false, scrollTimer: null }"
                    :class="{ 'is-scrolling': isScrolling }"
                    @scroll="
                        isScrolling = true;
                        clearTimeout(scrollTimer);
                        scrollTimer = setTimeout(() => { isScrolling = false; }, 800);
                    "
                >
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
