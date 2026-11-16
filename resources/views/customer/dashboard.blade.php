<x-panel :title="__('Mi área de cliente')">
    <div class="grid gap-6 sm:grid-cols-2">
        <x-card>
            <h3 class="font-semibold mb-3">{{ __('Últimos pedidos') }}</h3>
            @forelse ($recentOrders as $order)
                <a href="{{ route('customer.orders.show', $order) }}" class="flex justify-between border-t py-2 text-sm hover:bg-gray-50">
                    <span>{{ $order->order_number }}</span>
                    <x-status-badge :status="$order->status" />
                </a>
            @empty
                <p class="text-sm text-gray-500">{{ __('Aún no has comprado nada.') }} <a href="{{ route('catalog.index') }}" class="text-indigo-700">{{ __('Ver catálogo') }}</a></p>
            @endforelse
            <a href="{{ route('customer.orders.index') }}" class="mt-3 inline-block text-sm text-indigo-700">{{ __('Todos mis pedidos') }} →</a>
        </x-card>
        <x-card>
            <h3 class="font-semibold mb-1">{{ __('Direcciones de entrega') }}</h3>
            <p class="text-3xl font-semibold">{{ $addressesCount }}</p>
            <a href="{{ route('customer.addresses.index') }}" class="text-sm text-indigo-700">{{ __('Ver direcciones') }} →</a>
        </x-card>
    </div>
</x-panel>
