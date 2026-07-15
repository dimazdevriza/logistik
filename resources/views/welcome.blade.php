<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Log in') }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        @if (Route::has('login'))
            @auth
                <script>window.location='{{ route('dashboard') }}';</script>
            @else
                <x-layouts::auth :title="__('Log in')">
                    <div class="flex flex-col gap-6">
                        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

                        <x-auth-session-status class="text-center" :status="session('status')" />

                        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                            @csrf

                            <flux:input
                                name="email"
                                :label="__('Email address')"
                                :value="old('email')"
                                type="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="email@example.com"
                            />

                            <div class="relative">
                                <flux:input
                                    name="password"
                                    :label="__('Password')"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    :placeholder="__('Password')"
                                    viewable
                                />

                                @if (Route::has('password.request'))
                                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                                        {{ __('Forgot your password?') }}
                                    </flux:link>
                                @endif
                            </div>

                            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                            <div class="flex items-center justify-end">
                                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                                    {{ __('Log in') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </x-layouts::auth>
            @endauth
        @endif
    </body>
</html>
