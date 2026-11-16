<x-public-layout>
    <section class="text-center py-10">
        <h1 class="text-4xl font-bold tracking-tight">{{ __('Artesanía local, de taller a tu casa') }}</h1>
        <p class="mt-4 text-lg text-gray-600">{{ __('Piezas únicas de talleres verificados: cerámica, madera, cuero, joyería y textil.') }}</p>

        <form action="{{ route('catalog.index') }}" method="GET" class="mt-8 flex justify-center gap-2">
            <x-input name="q" type="search" class="w-80" :placeholder="__('Busca jarrones, cuero, lino...')" />
            <x-button>{{ __('Buscar') }}</x-button>
        </form>
    </section>

    <section class="mt-6">
        <h2 class="text-2xl font-semibold mb-4">{{ __('Piezas destacadas') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($featured as $product)
                <x-product-card :product="$product" />
            @empty
                <p class="text-gray-500">{{ __('Todavía no hay piezas destacadas.') }}</p>
            @endforelse
        </div>
    </section>

    @if ($topRated->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-2xl font-semibold mb-4">{{ __('Mejor valoradas') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($topRated as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-12">
        <h2 class="text-2xl font-semibold mb-4">{{ __('Talleres') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($shops as $shop)
                <a href="{{ route('shops.show', $shop) }}" class="block bg-white rounded-lg shadow p-4 hover:shadow-md">
                    <h3 class="font-semibold">{{ $shop->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $shop->craft_type }} · {{ trans_choice(':count pieza|:count piezas', $shop->products_count) }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-public-layout>
