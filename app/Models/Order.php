<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /** Estados en los que el comprador aún puede cancelar. */
    public const CANCELLABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_PAID];

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
        'shipping_name',
        'shipping_phone',
        'shipping_street',
        'shipping_city',
        'shipping_province',
        'shipping_postal_code',
        'shipping_country',
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
                'product_title',
                'variant_name',
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

    /*
    |--------------------------------------------------------------------------
    | Dirección de envío congelada (corrección Sprint 2)
    |--------------------------------------------------------------------------
    */

    /**
     * Copia los datos de una dirección en las columnas shipping_* del pedido.
     * A partir de aquí, editar o borrar la dirección original no altera la venta.
     */
    public static function shippingSnapshotFrom(Address $address): array
    {
        return [
            'address_id' => $address->id,
            'shipping_name' => $address->name,
            'shipping_phone' => $address->phone,
            'shipping_street' => $address->street,
            'shipping_city' => $address->city,
            'shipping_province' => $address->province,
            'shipping_postal_code' => $address->postal_code,
            'shipping_country' => $address->country,
        ];
    }

    public function getShippingAddressLineAttribute(): string
    {
        return "{$this->shipping_street}, {$this->shipping_postal_code} {$this->shipping_city} "
            ."({$this->shipping_province}), {$this->shipping_country}";
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope simple: pedidos en uno o varios estados.
     */
    public function scopeWithStatus(Builder $query, string|array $status): Builder
    {
        return $query->whereIn('status', (array) $status);
    }

    /**
     * Scope complejo: pedidos que un usuario puede ver según su rol.
     *  - admin / moderator: todos.
     *  - artisan: solo los pedidos en los que su taller tiene un subpedido.
     *  - resto (customer): solo sus propias compras.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['admin', 'moderator'])) {
            return $query;
        }

        if ($user->hasRole('artisan')) {
            $shopId = $user->shop?->id;

            return $query->whereHas('orderShops', fn (Builder $q) => $q->where('shop_id', $shopId ?? 0));
        }

        return $query->where('customer_id', $user->id);
    }

    /**
     * Scope: pedidos pendientes de pago creados antes de una fecha.
     */
    public function scopeUnpaidOlderThan(Builder $query, \DateTimeInterface $limit): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('created_at', '<', $limit)
            ->whereDoesntHave('payments', fn (Builder $q) => $q->where('status', 'completed'));
    }
}
