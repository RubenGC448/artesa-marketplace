<x-panel :title="__('Disputas e incidencias')">
    <x-card>
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th>{{ __('Pedido') }}</th><th>{{ __('Cliente') }}</th><th>{{ __('Taller') }}</th><th>{{ __('Motivo') }}</th><th>{{ __('Estado') }}</th></tr></thead>
            <tbody>
                @forelse ($disputes as $dispute)
                    <tr class="border-t">
                        <td class="py-2">{{ $dispute->order?->order_number }}</td>
                        <td>{{ $dispute->customer?->name }}</td>
                        <td>{{ $dispute->shop?->name }}</td>
                        <td>{{ $dispute->reason }}</td>
                        <td>{{ $dispute->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-2 text-gray-500">{{ __('No hay disputas.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-panel>
