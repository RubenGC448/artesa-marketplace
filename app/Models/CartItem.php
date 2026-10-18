<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'product_variant_id',
        'quantity',
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
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Usuario propietario del carrito (si está autenticado).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Producto añadido a la cesta (1:N inverso).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variante seleccionada, si aplica (1:N inverso).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accesores y Helpers de Carrito
    |--------------------------------------------------------------------------
    */

    /**
     * Precio unitario efectivo sumando suplemento de la variante si existe.
     */
    public function getEffectiveUnitPriceAttribute(): float
    {
        $price = (float) $this->product->base_price;

        if ($this->variant) {
            $price += (float) $this->variant->price_modifier;
        }

        return round($price, 2);
    }

    /**
     * Importe subtotal del artículo en la cesta.
     */
    public function getSubtotalAttribute(): float
    {
        return round($this->effective_unit_price * (int) $this->quantity, 2);
    }
}
