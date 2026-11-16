<x-panel :title="__('Hoja de ruta')">
    @forelse ($shipments as $shipment)
        <x-card>
            <div class="flex flex-wrap justify-between gap-2">
                <div>
                    <p class="font-semibold">{{ $shipment->orderShop->order->order_number }} · {{ $shipment->orderShop->shop->name }} <x-status-badge :status="$shipment->status" /></p>
                    <p class="text-sm text-gray-500">{{ $shipment->orderShop->order->shipping_name }} — {{ $shipment->orderShop->order->shipping_address_line }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('courier.shipments.update', $shipment) }}" class="mt-4 grid gap-4 sm:grid-cols-4 items-end">
                @csrf @method('PATCH')
                <x-form-field for="status" :label="__('Estado')">
                    <x-select name="status" class="w-full" :options="$statuses" :selected="$shipment->status" />
                </x-form-field>
                <x-form-field for="carrier" :label="__('Transportista')">
                    <x-input name="carrier" class="w-full" :value="$shipment->carrier" />
                </x-form-field>
                <x-form-field for="tracking_number" :label="__('Seguimiento')">
                    <x-input name="tracking_number" class="w-full" :value="$shipment->tracking_number" />
                </x-form-field>
                <x-button>{{ __('Guardar') }}</x-button>
            </form>
        </x-card>
    @empty
        <x-card>{{ __('No hay envíos pendientes.') }}</x-card>
    @endforelse
</x-panel>
