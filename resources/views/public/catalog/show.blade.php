<x-public-layout :title="$product->title">
    <a href="{{ route('catalog.index') }}" class="text-sm text-indigo-700">← {{ __('Volver al catálogo') }}</a>

    <div class="mt-4 grid gap-8 lg:grid-cols-2">
        <div>
            <p class="text-sm uppercase tracking-wide text-gray-500">
                <a href="{{ route('shops.show', $product->shop) }}" class="hover:underline">{{ $product->shop->name }}</a>
                @if ($product->category) · {{ $product->category->name }} @endif
            </p>
            <h1 class="mt-1 text-3xl font-bold">{{ $product->title }}</h1>
            <p class="mt-3 text-2xl text-indigo-700">{{ number_format((float) $product->base_price, 2, ',', '.') }} €</p>
            <p class="text-xs text-gray-500">{{ __('IVA incluido (:rate %)', ['rate' => rtrim(rtrim($product->tax_rate, '0'), '.')]) }}</p>

            @if ($product->available_from && $product->available_from->isFuture())
                <p class="mt-2 text-sm text-amber-700">{{ __('Disponible a partir del :date', ['date' => $product->available_from->format('d/m/Y')]) }}</p>
            @endif

            <p class="mt-6 text-gray-700 whitespace-pre-line">{{ $product->description }}</p>

            @if ($product->tags->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($product->tags as $tag)
                        <span class="rounded-full bg-stone-200 px-3 py-1 text-xs">{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            @if ($product->variants->isNotEmpty())
                <h2 class="font-semibold mb-3">{{ __('Variantes') }}</h2>
                <ul class="divide-y">
                    @foreach ($product->variants as $variant)
                        <li class="py-2 flex justify-between text-sm">
                            <span>{{ $variant->name }}</span>
                            <span>
                                {{ number_format((float) $product->base_price + (float) $variant->price_modifier, 2, ',', '.') }} €
                                · {{ $variant->stock > 0 ? trans_choice(':count disponible|:count disponibles', $variant->stock) : __('Agotado') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm">{{ $product->stock > 0 ? trans_choice(':count unidad disponible|:count unidades disponibles', $product->stock) : __('Agotado') }}</p>
            @endif
        </div>
    </div>

    <section class="mt-12">
        <h2 class="text-xl font-semibold mb-4">{{ __('Valoraciones') }}</h2>
        @forelse ($product->reviews as $review)
            <div class="bg-white rounded-lg shadow p-4 mb-3">
                <p class="text-amber-600">{{ str_repeat('★', $review->rating) }}</p>
                <p class="mt-1">{{ $review->comment }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $review->customer?->name }}</p>
                @if ($review->artisan_reply)
                    <p class="mt-2 border-l-4 border-indigo-200 pl-3 text-sm text-gray-600">{{ $review->artisan_reply }}</p>
                @endif
            </div>
        @empty
            <p class="text-gray-500">{{ __('Esta pieza aún no tiene valoraciones.') }}</p>
        @endforelse
    </section>
</x-public-layout>
