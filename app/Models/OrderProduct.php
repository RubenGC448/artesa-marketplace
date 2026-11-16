<?php

namespace App\Models;

use App\Services\Orders\OrderLineGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProduct extends Pivot
{
    /** @use HasFactory<\Database\Factories\OrderProductFactory> */
    use HasFactory;

    /**
     * Tabla pivote asociada.
     *
     * @var string
     */
    protected $table = 'order_product';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Indica si el ID es autoincremental (posee clave primaria id).
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'order_shop_id',
        'product_id',
        'product_variant_id',
        'product_title',
        'variant_name',
        'quantity',
        'unit_price',
        'applied_tax',
        'artisan_commission_rate',
        'line_status',
        'customization_notes',
    ];

    /**
     * Tipos de datos que deben castearse.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'applied_tax' => 'decimal:2',
            'artisan_commission_rate' => 'decimal:2',
        ];
    }

    /**
     * Antes de guardar cualquier línea (create, attach con pivote o update)
     * se comprueba su coherencia con pedido, subpedido, taller y variante.
     */
    protected static function booted(): void
    {
        static::saving(function (OrderProduct $line) {
            app(OrderLineGuard::class)->assertConsistent($line);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Pedido global al que pertenece esta línea (1:N inverso).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Sub-pedido de la tienda encargada de preparar esta pieza (1:N inverso).
     */
    public function orderShop(): BelongsTo
    {
        return $this->belongsTo(OrderShop::class);
    }

    /**
     * Producto comprado (1:N inverso).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variante elegida para el producto, si aplica (1:N inverso).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos y Helpers de Dominio (Cálculos de Línea)
    |--------------------------------------------------------------------------
    */

    /**
     * Importe subtotal de esta línea (precio congelado * unidades).
     */
    public function getSubtotalAttribute(): float
    {
        return round((float) $this->unit_price * (int) $this->quantity, 2);
    }

    /**
     * Importe neto correspondiente al artesano tras deducir la comisión congelada.
     */
    public function getArtisanEarningsAttribute(): float
    {
        $unitPrice = (float) $this->unit_price;
        $commissionRate = (float) $this->artisan_commission_rate;
        $commissionPerUnit = ($unitPrice * $commissionRate) / 100;
        $netPerUnit = $unitPrice - $commissionPerUnit;

        return round($netPerUnit * (int) $this->quantity, 2);
    }

    /**
     * Cuota de IVA incluida en el subtotal de la línea (precios con IVA incluido).
     */
    public function getTaxAmountAttribute(): float
    {
        $rate = (float) $this->applied_tax;

        return round($this->subtotal - ($this->subtotal / (1 + $rate / 100)), 2);
    }
}
