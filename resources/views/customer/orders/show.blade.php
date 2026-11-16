<x-panel :title="__('Pedido :number', ['number' => $order->order_number])">
    <x-card>
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <p><x-status-badge :status="$order->status" /> <span class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</span></p>
                <p class="mt-2 text-sm"><strong>{{ __('Envío a') }}:</strong> {{ $order->shipping_name }} — {{ $order->shipping_address_line }}</p>
            </div>
            <div class="text-right text-sm">
                <p>{{ __('Subtotal') }}: {{ number_format((float) $order->subtotal, 2, ',', '.') }} €</p>
                <p>{{ __('Portes') }}: {{ number_format((float) $order->shipping_cost, 2, ',', '.') }} €</p>
                <p class="text-lg font-semibold">{{ __('Total') }}: {{ number_format((float) $order->total, 2, ',', '.') }} €</p>
            </div>
        </div>

        @can('cancel', $order)
            <form method="POST" action="{{ route('customer.orders.cancel', $order) }}" class="mt-4">
                @csrf @method('PATCH')
                <x-danger-button type="submit">{{ __('Cancelar pedido') }}</x-danger-button>
            </form>
        @endcan
    </x-card>

    @foreach ($order->orderShops as $suborder)
        <x-card>
            <div class="flex justify-between">
                <h3 class="font-semibold">{{ $suborder->shop->name }} <x-status-badge :status="$suborder->status" /></h3>
                <span class="text-sm">{{ number_format((float) $suborder->total, 2, ',', '.') }} €</span>
            </div>
            <ul class="mt-2 text-sm divide-y">
                @foreach ($suborder->orderProducts as $line)
                    <li class="py-1 flex justify-between">
                        <span>{{ $line->quantity }} × {{ $line->product_title }} @if ($line->variant_name) ({{ $line->variant_name }}) @endif</span>
                        <span>{{ number_format($line->subtotal, 2, ',', '.') }} € · {{ __('IVA :rate %', ['rate' => (float) $line->applied_tax]) }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($suborder->latestShipment)
                <p class="mt-2 text-xs text-gray-500">{{ $suborder->latestShipment->carrier }} {{ $suborder->latestShipment->tracking_number }} · <x-status-badge :status="$suborder->latestShipment->status" /></p>
            @endif
        </x-card>
    @endforeach
</x-panel>
