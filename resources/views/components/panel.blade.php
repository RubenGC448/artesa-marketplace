{{-- Página de panel privado con cabecera y mensajes flash --}}
@props(['title'])

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
