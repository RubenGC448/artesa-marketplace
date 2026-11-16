<x-public-layout :title="$shop->name">
    <h1 class="text-3xl font-bold">{{ $shop->name }}</h1>
    <p class="text-gray-500">{{ $shop->craft_type }}</p>
    <p class="mt-4 max-w-3xl text-gray-700">{{ $shop->bio }}</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($products as $product)
            <x-product-card :product="$product->setRelation('shop', $shop)" />
        @empty
            <p class="text-gray-500">{{ __('Este taller aún no tiene piezas publicadas.') }}</p>
        @endforelse
    </div>
</x-public-layout>
