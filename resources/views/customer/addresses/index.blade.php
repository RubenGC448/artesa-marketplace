<x-panel :title="__('Mis direcciones')">
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($addresses as $address)
            <x-card>
                <p class="font-medium">{{ $address->name }} @if ($address->is_default) <span class="text-xs text-indigo-700">({{ __('predeterminada') }})</span> @endif</p>
                <p class="text-sm text-gray-600">{{ $address->formatted_address }}</p>
                <p class="text-sm text-gray-600">{{ $address->phone }}</p>
            </x-card>
        @empty
            <x-card>{{ __('No tienes direcciones guardadas.') }}</x-card>
        @endforelse
    </div>
</x-panel>
