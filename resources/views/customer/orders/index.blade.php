<x-panel :title="__('Mis pedidos')">
    <x-card>
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th>{{ __('Pedido') }}</th><th>{{ __('Fecha') }}</th><th>{{ __('Talleres') }}</th><th class="text-right">{{ __('Total') }}</th><th>{{ __('Estado') }}</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-t">
                        <td class="py-2"><a href="{{ route('customer.orders.show', $order) }}" class="text-indigo-700">{{ $order->order_number }}</a></td>
                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                        <td>{{ $order->orderShops->pluck('shop.name')->join(', ') }}</td>
                        <td class="text-right">{{ number_format((float) $order->total, 2, ',', '.') }} €</td>
                        <td><x-status-badge :status="$order->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-2 text-gray-500">{{ __('Aún no has hecho ningún pedido.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $orders->links() }}</div>
    </x-card>
</x-panel>
