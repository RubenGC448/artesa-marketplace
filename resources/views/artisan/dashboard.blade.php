<x-panel :title="__('Panel del artesano')">
    @if (! $shop)
        <x-card>{{ __('Todavía no tienes un taller asociado. Contacta con la administración.') }}</x-card>
    @else
        @unless ($shop->is_verified)
            <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                {{ __('Tu taller está pendiente de verificación: podrás publicar piezas cuando la moderación lo apruebe.') }}
            </div>
        @endunless

        <div class="grid gap-6 sm:grid-cols-3">
            <x-card>
                <p class="text-sm text-gray-500">{{ $shop->name }}</p>
                <p class="text-3xl font-semibold">{{ $productsCount }}</p>
                <a href="{{ route('artisan.products.index') }}" class="text-sm text-indigo-700">{{ __('Gestionar catálogo') }} →</a>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">{{ __('Subpedidos por preparar') }}</p>
                <p class="text-3xl font-semibold">{{ $pendingSuborders }}</p>
                <a href="{{ route('artisan.orders.index') }}" class="text-sm text-indigo-700">{{ __('Ver pedidos') }} →</a>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500 mb-2">{{ __('Stock bajo') }}</p>
                @forelse ($lowStock as $product)
                    <p class="text-sm">{{ $product->title }} — {{ $product->stock }}</p>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Todo en orden.') }}</p>
                @endforelse
            </x-card>
        </div>
    @endif
</x-panel>
