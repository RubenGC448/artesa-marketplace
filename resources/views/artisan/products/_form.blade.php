{{-- Formulario compartido de alta/edición. Usa los componentes input, label, select, fecha, textarea y checkbox. --}}
<div class="grid gap-6 sm:grid-cols-2">
    <x-form-field for="title" :label="__('Título')" class="sm:col-span-2">
        <x-input id="title" name="title" type="text" class="block w-full" :value="old('title', $product->title)" required />
    </x-form-field>

    <x-form-field for="category_id" :label="__('Categoría')">
        <x-select id="category_id" name="category_id" class="block w-full" :options="$categories" :selected="old('category_id', $product->category_id)" :placeholder="__('Elige una categoría')" />
    </x-form-field>

    <x-form-field for="status" :label="__('Estado')" :help="__('Solo las piezas publicadas aparecen en el catálogo.')">
        <x-select id="status" name="status" class="block w-full" :options="$statuses" :selected="old('status', $product->status)" />
    </x-form-field>

    <x-form-field for="base_price" :label="__('Precio (IVA incluido)')">
        <x-input id="base_price" name="base_price" type="number" step="0.01" min="0.50" class="block w-full" :value="old('base_price', $product->base_price)" required />
    </x-form-field>

    <x-form-field for="tax_rate" :label="__('Tipo de IVA')">
        <x-select id="tax_rate" name="tax_rate" class="block w-full" :options="$taxRates" :selected="old('tax_rate', $product->tax_rate)" />
    </x-form-field>

    <x-form-field for="stock" :label="__('Stock')" :help="__('Si la pieza tiene variantes, el stock se gestiona en cada variante.')">
        <x-input id="stock" name="stock" type="number" min="0" class="block w-full" :value="old('stock', $product->stock)" required />
    </x-form-field>

    <x-form-field for="available_from" :label="__('Disponible desde')" :help="__('Opcional, para piezas bajo encargo.')">
        <x-date-input id="available_from" name="available_from" class="block w-full" :value="old('available_from', $product->available_from)" :min="now()" />
    </x-form-field>

    <x-form-field for="description" :label="__('Descripción')" class="sm:col-span-2">
        <x-textarea id="description" name="description" class="block w-full">{{ old('description', $product->description) }}</x-textarea>
    </x-form-field>

    <div class="sm:col-span-2">
        <label for="featured" class="inline-flex items-center gap-2">
            <x-checkbox id="featured" name="featured" value="1" :checked="(bool) old('featured', $product->featured)" />
            <span class="text-sm text-gray-700">{{ __('Destacar en la portada') }}</span>
        </label>
        <x-input-error for="featured" />
    </div>
</div>
