<x-panel :title="__('Panel de reparto')">
    <div class="grid gap-6 sm:grid-cols-2">
        <x-card>
            <p class="text-sm text-gray-500">{{ __('Envíos por recoger') }}</p>
            <p class="text-3xl font-semibold">{{ $pending }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">{{ __('En reparto') }}</p>
            <p class="text-3xl font-semibold">{{ $inTransit }}</p>
        </x-card>
    </div>
    <a href="{{ route('courier.shipments.index') }}" class="text-indigo-700">{{ __('Ver hoja de ruta') }} →</a>
</x-panel>
