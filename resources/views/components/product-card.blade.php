@props(['product'])

<a href="{{ route('catalog.show', $product) }}" class="block bg-white rounded-lg shadow hover:shadow-md transition p-4">
    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $product->shop?->name }}</p>
    <h3 class="mt-1 font-semibold text-gray-900">{{ $product->title }}</h3>
    <p class="mt-2 text-indigo-700 font-medium">{{ number_format((float) $product->base_price, 2, ',', '.') }} €</p>
    @isset($product->avg_rating)
        <p class="mt-1 text-sm text-amber-600">★ {{ number_format((float) $product->avg_rating, 1, ',', '.') }}</p>
    @endisset
</a>
