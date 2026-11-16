<x-panel :title="__('Nueva pieza')">
    <x-card>
        <form method="POST" action="{{ route('artisan.products.store') }}" class="space-y-6">
            @csrf
            @include('artisan.products._form')
            <div class="flex justify-end gap-3">
                <a href="{{ route('artisan.products.index') }}" class="text-sm text-gray-600 self-center">{{ __('Cancelar') }}</a>
                <x-button>{{ __('Guardar') }}</x-button>
            </div>
        </form>
    </x-card>
</x-panel>
