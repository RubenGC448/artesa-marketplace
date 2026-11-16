<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price_modifier',
        'stock',
    ];

    /**
     * Tipos de datos que deben castearse.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_modifier' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Producto base al que pertenece esta variante (1:N inverso).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Líneas de compra de pedidos donde se especificó esta variante (1:N).
     */
    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * Elementos del carrito donde se seleccionó esta variante (1:N).
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Precio final de la variante (precio base del producto + modificador).
     */
    public function getFinalPriceAttribute(): float
    {
        return round((float) $this->product->base_price + (float) $this->price_modifier, 2);
    }

    /**
     * Scope: variantes con stock igual o inferior al umbral.
     */
    public function scopeLowStock(Builder $query, int $threshold): Builder
    {
        return $query->where('stock', '<=', $threshold);
    }
}
