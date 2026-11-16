<x-public-layout :title="__('Catálogo')">
    <h1 class="text-3xl font-bold mb-6">{{ __('Catálogo') }}</h1>

    <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-wrap items-end gap-4 mb-8">
        <x-form-field for="q" :label="__('Buscar')">
            <x-input id="q" name="q" type="search" :value="$filters['q'] ?? ''" class="w-72" />
        </x-form-field>

        <x-form-field for="category" :label="__('Categoría')">
            <x-select id="category" name="category" :options="$categories" :selected="$filters['category'] ?? null" :placeholder="__('Todas')" />
        </x-form-field>

        <x-button>{{ __('Filtrar') }}</x-button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($products as $product)
            <x-product-card :product="$product" />
        @empty
            <p class="text-gray-500">{{ __('No hay piezas que coincidan con la búsqueda.') }}</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $products->links() }}</div>
</x-public-layout>
