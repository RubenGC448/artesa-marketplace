<x-panel :title="__('Verificación de talleres')">
    <x-card>
        <h3 class="font-semibold mb-3">{{ __('Pendientes de verificar') }}</h3>
        @forelse ($shops as $shop)
            <div class="flex items-center justify-between border-t py-3">
                <div>
                    <p class="font-medium">{{ $shop->name }} <span class="text-gray-500 text-sm">({{ $shop->cif_nif }})</span></p>
                    <p class="text-sm text-gray-500">{{ $shop->craft_type }} · {{ $shop->user->name }} · {{ $shop->user->email }}</p>
                </div>
                @can('verify', $shop)
                    <form method="POST" action="{{ route('admin.shops.verify', $shop) }}">
                        @csrf @method('PATCH')
                        <x-button>{{ __('Verificar') }}</x-button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="text-gray-500">{{ __('No hay talleres pendientes.') }}</p>
        @endforelse
    </x-card>

    <x-card>
        <h3 class="font-semibold mb-3">{{ __('Talleres verificados') }}</h3>
        @foreach ($verified as $shop)
            <div class="flex items-center justify-between border-t py-3">
                <p>{{ $shop->name }} <span class="text-gray-500 text-sm">· {{ $shop->user->name }}</span></p>
                @can('verify', $shop)
                    <form method="POST" action="{{ route('admin.shops.revoke', $shop) }}">
                        @csrf @method('PATCH')
                        <x-danger-button type="submit">{{ __('Retirar verificación') }}</x-danger-button>
                    </form>
                @endcan
            </div>
        @endforeach
    </x-card>
</x-panel>
