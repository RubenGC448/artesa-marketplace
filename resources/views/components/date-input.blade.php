{{--
    Campo de fecha reutilizable. Acepta Carbon, DateTime o texto Y-m-d.
    Uso: <x-date-input name="available_from" :value="old('available_from', $product->available_from)" :min="now()" />
--}}
@props(['value' => null, 'min' => null, 'max' => null, 'disabled' => false])

@php
    $format = fn ($date) => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date;
@endphp

<input type="date"
       value="{{ $format($value) }}"
       @if ($min) min="{{ $format($min) }}" @endif
       @if ($max) max="{{ $format($max) }}" @endif
       {{ $disabled ? 'disabled' : '' }}
       {!! $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) !!}>
