{{-- Layout de la zona pública (escaparate) --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'Artesa') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-stone-50 text-gray-900">
        <header class="bg-white border-b">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight">Artesa</a>

                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ route('catalog.index') }}" class="hover:text-indigo-700">{{ __('Catálogo') }}</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-indigo-700">{{ __('Mi panel') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-indigo-700">{{ __('Entrar') }}</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-500">{{ __('Crear cuenta') }}</a>
                        @endif
                    @endauth
                </div>
            </nav>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            {{ $slot }}
        </main>

        <footer class="border-t py-6 text-center text-sm text-gray-500">
            {{ __('Artesa · Marketplace local de productos artesanales') }}
        </footer>
    </body>
</html>
