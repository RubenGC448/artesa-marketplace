<x-panel :title="__('Pedidos de mi taller')">
    @forelse ($suborders as $suborder)
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-semibold">{{ $suborder->order->order_number }} <x-status-badge :status="$suborder->status" /></p>
                    <p class="text-sm text-gray-500">{{ $suborder->order->created_at->format('d/m/Y') }} · {{ __('Envío a') }} {{ $suborder->order->shipping_city }}</p>
                </div>
                <p class="font-medium">{{ number_format((float) $suborder->total, 2, ',', '.') }} €</p>
            </div>

            <ul class="mt-3 text-sm divide-y">
                @foreach ($suborder->orderProducts as $line)
                    <li class="py-1 flex justify-between">
                        <span>{{ $line->quantity }} × {{ $line->product_title }} @if ($line->variant_name) ({{ $line->variant_name }}) @endif</span>
                        <x-status-badge :status="$line->line_status" />
                    </li>
                @endforeach
            </ul>

            @can('manageSuborder', $suborder)
                @if (in_array($suborder->status, \App\Models\OrderShop::CANCELLABLE_STATUSES, true))
                    <div class="mt-4 flex flex-wrap items-end gap-3">
                        <form method="POST" action="{{ route('artisan.orders.update-status', $suborder) }}" class="flex items-end gap-2">
                            @csrf @method('PATCH')
                            <x-form-field for="status" :label="__('Nuevo estado')">
                                <x-select name="status" :options="$statuses" :selected="$suborder->status" />
                            </x-form-field>
                            <x-button>{{ __('Actualizar') }}</x-button>
                        </form>

                        <form method="POST" action="{{ route('artisan.orders.cancel', $suborder) }}">
                            @csrf @method('PATCH')
                            <x-danger-button type="submit">{{ __('Cancelar mi parte') }}</x-danger-button>
                        </form>
                    </div>
                @endif
            @endcan
        </x-card>
    @empty
        <x-card>{{ __('No hay pedidos para tu taller.') }}</x-card>
    @endforelse

    @if ($suborders instanceof \Illuminate\Contracts\Pagination\Paginator)
        {{ $suborders->links() }}
    @endif
</x-panel>
