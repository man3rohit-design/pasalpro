<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('shop.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-950 antialiased">
    <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold tracking-tight">
                <x-filament::icon icon="heroicon-o-shopping-bag" class="h-7 w-7 text-primary-600" />
                {{ config('shop.name') }}
            </a>

            <nav class="flex items-center gap-5 text-sm font-medium text-gray-600">
                <a href="{{ route('products.index') }}" wire:navigate class="hover:text-primary-600">All products</a>
                <a href="{{ route('filament.account.resources.orders.index') }}" class="hover:text-primary-600">Track order</a>
            </nav>

            @unless (request()->routeIs('products.index'))
                <form action="{{ route('products.index') }}" method="get" class="order-last w-full sm:order-none sm:ml-auto sm:w-72">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input type="search" name="search" placeholder="Search noodles, rice, tea..." aria-label="Search products" />
                    </x-filament::input.wrapper>
                </form>
            @endunless

            <div class="ml-auto flex items-center gap-3">
                <livewire:cart-counter />

                @auth
                    <x-filament::button tag="a" :href="route('filament.account.resources.orders.index')" color="gray" size="sm" icon="heroicon-m-user-circle">
                        My orders
                    </x-filament::button>
                @else
                    <x-filament::button tag="a" :href="route('filament.account.auth.login')" color="gray" size="sm">
                        Sign in
                    </x-filament::button>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-sm text-gray-600 sm:grid-cols-3 sm:px-6">
            <div>
                <p class="font-semibold text-gray-950">{{ config('shop.name') }}</p>
                <p>{{ config('shop.address') }}</p>
                <p>Phone {{ config('shop.phone') }}</p>
            </div>
            <div>
                <p class="font-semibold text-gray-950">Delivery</p>
                <p>Free home delivery inside Kathmandu valley. Pay cash or Fonepay QR when your order arrives.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-950">Opening hours</p>
                <p>Sunday to Friday, 8 am to 8 pm</p>
                <p>Saturday, 9 am to 6 pm</p>
            </div>
        </div>
    </footer>

    @livewire('notifications')
    @filamentScripts
</body>

</html>
