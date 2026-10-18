<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'address_id',
        'status',
        'subtotal',
        'shipping_cost',
        'total',
        'notes',
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
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Usuario comprador que realizó el pedido (1:N inverso).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Dirección de entrega congelada en el momento de la compra (1:N inverso).
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Particiones del pedido por cada tienda participante en el checkout (1:N).
     */
    public function orderShops(): HasMany
    {
        return $this->hasMany(OrderShop::class);
    }

    /**
     * Líneas de producto adquiridas con importes congelados (1:N).
     */
    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * Productos adquiridos en el pedido (N:M con tabla pivote extendida).
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->using(OrderProduct::class)
            ->withPivot([
                'order_shop_id',
                'product_variant_id',
                'quantity',
                'unit_price',
                'applied_tax',
                'artisan_commission_rate',
                'line_status',
                'customization_notes',
            ])
            ->withTimestamps();
    }

    /**
     * Pagos registrados contra este pedido (1:N).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Registro histórico de transiciones de estado del pedido (1:N).
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Reseñas verificadas emitidas a partir de este pedido (1:N).
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Reclamaciones / disputas abiertas sobre este pedido (1:N).
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }
}
