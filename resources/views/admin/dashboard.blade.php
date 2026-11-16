<x-panel :title="__('Administración y moderación')">
    <div class="grid gap-6 sm:grid-cols-3">
        <x-card>
            <p class="text-sm text-gray-500">{{ __('Talleres pendientes de verificar') }}</p>
            <p class="text-3xl font-semibold">{{ $pendingShops }}</p>
            <a href="{{ route('admin.shops.pending') }}" class="text-sm text-indigo-700">{{ __('Revisar') }} →</a>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">{{ __('Disputas abiertas') }}</p>
            <p class="text-3xl font-semibold">{{ $openDisputes }}</p>
            <a href="{{ route('admin.disputes.index') }}" class="text-sm text-indigo-700">{{ __('Ver disputas') }} →</a>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500 mb-2">{{ __('Pedidos por estado') }}</p>
            @foreach ($ordersByStatus as $status => $total)
                <div class="flex justify-between text-sm"><x-status-badge :status="$status" /> <span>{{ $total }}</span></div>
            @endforeach
        </x-card>
    </div>

    <x-card>
        <h3 class="font-semibold mb-3">{{ __('Más vendidos (últimos 30 días)') }}</h3>
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th>{{ __('Producto') }}</th><th>{{ __('Taller') }}</th><th class="text-right">{{ __('Unidades') }}</th></tr></thead>
            <tbody>
                @forelse ($bestSelling as $product)
                    <tr class="border-t"><td class="py-1">{{ $product->title }}</td><td>{{ $product->shop->name }}</td><td class="text-right">{{ $product->units_sold }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-2 text-gray-500">{{ __('Sin ventas entregadas en el periodo.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-panel>
