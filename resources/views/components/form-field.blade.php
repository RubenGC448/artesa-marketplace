{{--
    Envoltorio de campo: etiqueta + control + error de validación.
    Uso: <x-form-field for="title" :label="__('Título')"> <x-input id="title" name="title" /> </x-form-field>
--}}
@props(['for', 'label', 'help' => null])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <x-label :for="$for" :value="$label" />
    {{ $slot }}
    @if ($help)
        <p class="text-xs text-gray-500">{{ $help }}</p>
    @endif
    <x-input-error :for="$for" />
</div>
