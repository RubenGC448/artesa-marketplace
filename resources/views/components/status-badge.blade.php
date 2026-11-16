@props(['status'])

@php
    $styles = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'paid' => 'bg-blue-100 text-blue-800',
        'preparing' => 'bg-indigo-100 text-indigo-800',
        'ready' => 'bg-purple-100 text-purple-800',
        'shipped' => 'bg-sky-100 text-sky-800',
        'in_transit' => 'bg-sky-100 text-sky-800',
        'delivered' => 'bg-green-100 text-green-800',
        'active' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'failed' => 'bg-red-100 text-red-800',
        'draft' => 'bg-gray-100 text-gray-800',
        'inactive' => 'bg-gray-100 text-gray-800',
    ];

    $labels = [
        'pending' => __('Pendiente'),
        'paid' => __('Pagado'),
        'preparing' => __('En preparación'),
        'ready' => __('Listo'),
        'shipped' => __('Enviado'),
        'in_transit' => __('En reparto'),
        'delivered' => __('Entregado'),
        'active' => __('Publicado'),
        'cancelled' => __('Cancelado'),
        'failed' => __('Incidencia'),
        'draft' => __('Borrador'),
        'inactive' => __('Retirado'),
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.($styles[$status] ?? 'bg-gray-100 text-gray-800')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
