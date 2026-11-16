<x-panel :title="__('Catálogo del taller')">
    <div class="flex justify-end">
        @can('create', App\Models\Product::class)
            <a href="{{ route('artisan.products.create') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">{{ __('Nueva pieza') }}</a>
        @endcan
    </div>

    <x-card>
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th>{{ __('Pieza') }}</th><th>{{ __('Categoría') }}</th><th class="text-right">{{ __('Precio') }}</th><th class="text-right">{{ __('Stock') }}</th><th>{{ __('Estado') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="border-t">
                        <td class="py-2">{{ $product->title }}</td>
                        <td>{{ $product->category?->name }}</td>
                        <td class="text-right">{{ number_format((float) $product->base_price, 2, ',', '.') }} €</td>
                        <td class="text-right">{{ $product->variants->isNotEmpty() ? $product->variants->sum('stock').' ('.__('variantes').')' : $product->stock }}</td>
                        <td><x-status-badge :status="$product->status" /></td>
                        <td class="text-right space-x-2 whitespace-nowrap">
                            @can('update', $product)
                                <a href="{{ route('artisan.products.edit', $product) }}" class="text-indigo-700">{{ __('Editar') }}</a>
                            @endcan
                            @can('delete', $product)
                                <form method="POST" action="{{ route('artisan.products.destroy', $product) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-700">{{ __('Eliminar') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-2 text-gray-500">{{ __('Aún no has creado piezas.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($products instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="mt-4">{{ $products->links() }}</div>
        @endif
    </x-card>
</x-panel>
