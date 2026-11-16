{{--
    Select reutilizable.
    Uso: <x-select name="category_id" :options="$categories" :selected="old('category_id', $product->category_id)" placeholder="Elige..." />
--}}
@props(['options' => [], 'selected' => null, 'placeholder' => null, 'disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) !!}>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $value === (string) $selected)>{{ $label }}</option>
    @endforeach
</select>
