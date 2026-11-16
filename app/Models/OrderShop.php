<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderShop extends Model
{
    /** @use HasFactory<\Database\Factories\OrderShopFactory> */
    use HasFactory;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /** Un taller solo puede cancelar su parte antes de enviarla. */
    public const CANCELLABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_PREPARING, self::STATUS_READY];

    /** Transiciones que el artesano puede aplicar a su subpedido. */
    public const ARTISAN_STATUSES = [self::STATUS_PREPARING, self::STATUS_READY, self::STATUS_SHIPPED];

    protected $fillable = [
        'order_id',
        'shop_id',
        'status',
        'subtotal',
        'shipping_cost',
        'total',
    ];

    /**
     * Tipos de datos que deben castearse.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Pedido global al que pertenece esta sub-orden (1:N inverso).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Taller artesano encargado de despachar esta parte del pedido (1:N inverso).
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Líneas de compra pertenecientes a este taller (1:N).
     */
    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * Envíos asociados a la parte del pedido gestionada por este taller (1:N).
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Envío más reciente de este sub-pedido.
     */
    public function latestShipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }
}
