<?php

/*
|--------------------------------------------------------------------------
| Parámetros de negocio de Artesa
|--------------------------------------------------------------------------
| Valores configurables (sin constantes escritas en el código) usados por
| los servicios de pedidos, los comandos y los formularios.
*/

return [

    /*
    | Tipos de IVA permitidos por producto. Cada producto guarda su propio
    | tipo (products.tax_rate) y cada línea de pedido lo congela
    | (order_product.applied_tax). Los precios se expresan con IVA incluido.
    */
    'tax_rates' => [
        '21.00' => 'General (21 %)',
        '10.00' => 'Reducido (10 %)',
        '4.00' => 'Superreducido (4 %)',
    ],

    'default_tax_rate' => (float) env('ARTESA_DEFAULT_TAX_RATE', 21.00),

    /*
    | Comisión por defecto de la plataforma para talleres nuevos (porcentaje).
    | La comisión vigente de cada taller está en shops.commission_rate y se
    | congela en cada línea (order_product.artisan_commission_rate).
    */
    'default_commission_rate' => (float) env('ARTESA_DEFAULT_COMMISSION', 10.00),

    /*
    | Portes: tarifa plana por subpedido (cada taller envía su parte).
    */
    'shipping_flat_rate' => (float) env('ARTESA_SHIPPING_FLAT_RATE', 4.95),

    /*
    | Horas tras las que un pedido pendiente de pago se cancela
    | automáticamente (comando artesa:cancel-unpaid-orders).
    */
    'unpaid_order_hours' => (int) env('ARTESA_UNPAID_ORDER_HOURS', 48),

    /*
    | Umbral de unidades a partir del cual se avisa de stock bajo
    | (comando artesa:low-stock-report).
    */
    'low_stock_threshold' => (int) env('ARTESA_LOW_STOCK_THRESHOLD', 3),

];
