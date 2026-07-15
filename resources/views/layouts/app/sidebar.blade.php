<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data
    x-init="
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    ">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            {{-- Scrollable nav area — account button stays pinned below --}}
            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain">
            <flux:sidebar.nav>
                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Platform') }}</span>
                    </x-slot>
                    @if(auth()->user()?->role === 'admin')
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            <span class="text-zinc-800 dark:text-zinc-100 font-semibold">{{ __('Dashboard Admin') }}</span>
                        </flux:sidebar.item>
                    @elseif(auth()->user()?->role === 'logistik')
                        <flux:sidebar.item icon="truck" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            <span class="text-zinc-800 dark:text-zinc-100 font-semibold">{{ __('Dashboard Logistik') }}</span>
                        </flux:sidebar.item>

                    @else
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Dashboard') }}</span>
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                {{-- Logistik & Admin menu items --}}
                @if(in_array(auth()->user()?->role, ['logistik', 'admin']))
                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Inventaris') }}</span>
                    </x-slot>

                    <flux:sidebar.item icon="cube" :href="route('logistik.materials')" :current="request()->routeIs('logistik.materials')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Material') }}</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wrench" :href="route('logistik.tools')" :current="request()->routeIs('logistik.tools')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Alat') }}</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" :href="route('logistik.suppliers')" :current="request()->routeIs('logistik.suppliers')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Supplier') }}</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('logistik.categories')" :current="request()->routeIs('logistik.categories')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Kategori') }}</span>
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Proyek') }}</span>
                    </x-slot>
                    <flux:sidebar.item icon="home-modern" :href="route('logistik.houses')" :current="request()->routeIs('logistik.houses*')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Rumah') }}</span>
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Transaksi') }}</span>
                    </x-slot>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('logistik.transaksi')" :current="request()->routeIs('logistik.transaksi')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Transaksi Logistik') }}</span>
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Log') }}</span>
                    </x-slot>
                    <flux:sidebar.item icon="document-text" :href="route('logistik.material-log')" :current="request()->routeIs('logistik.material-log')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Catatan Material') }}</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('logistik.tool-log')" :current="request()->routeIs('logistik.tool-log')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Catatan Alat') }}</span>
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Admin-only report items --}}
                @if(auth()->user()?->role === 'admin')
                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Laporan') }}</span>
                    </x-slot>
                    <flux:sidebar.item icon="currency-dollar" :href="route('admin.house-costs')" :current="request()->routeIs('admin.house-costs*')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Biaya Rumah') }}</span>
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                {{-- Admin-only menu items --}}
                @if(auth()->user()?->role === 'admin')
                <flux:sidebar.group class="grid">
                    <x-slot name="heading">
                        <span class="text-zinc-900 dark:text-white font-bold uppercase tracking-widest text-[10px]">{{ __('Administrasi') }}</span>
                    </x-slot>
                    <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                        <span class="text-zinc-800 dark:text-white font-semibold">{{ __('Manajemen User') }}</span>
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>
            </div>
            <x-desktop-user-menu class="hidden lg:block shrink-0" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate text-zinc-950 dark:text-zinc-50 font-black">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate text-zinc-800 dark:text-zinc-200 font-semibold">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        <flux:menu.item icon="moon"
                            x-data
                            @click.prevent="
                                if (document.documentElement.classList.contains('dark')) {
                                    document.documentElement.classList.remove('dark');
                                    localStorage.theme = 'light';
                                } else {
                                    document.documentElement.classList.add('dark');
                                    localStorage.theme = 'dark';
                                }
                            "
                        >
                            <span x-text="document.documentElement.classList.contains('dark') ? 'Light Mode' : 'Dark Mode'"></span>
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
