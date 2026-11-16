<x-panel :title="__('Editar «:title»', ['title' => $product->title])">
    <x-card>
        <form method="POST" action="{{ route('artisan.products.update', $product) }}" class="space-y-6">
            @csrf @method('PUT')
            @include('artisan.products._form')
            <div class="flex justify-end gap-3">
                <a href="{{ route('artisan.products.index') }}" class="text-sm text-gray-600 self-center">{{ __('Cancelar') }}</a>
                <x-button>{{ __('Guardar cambios') }}</x-button>
            </div>
        </form>
    </x-card>
</x-panel>
