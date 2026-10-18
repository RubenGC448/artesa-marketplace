<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'cif_nif',
        'bio',
        'craft_type',
        'latitude',
        'longitude',
        'is_verified',
        'commission_rate',
    ];

    /**
     * Tipos de datos que deben castearse.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'commission_rate' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Artesano titular al que pertenece el taller (1:1 inverso).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catálogo de productos elaborados por el taller (1:N).
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Sub-pedidos asignados a este taller en compras multi-tienda (1:N).
     */
    public function orderShops(): HasMany
    {
        return $this->hasMany(OrderShop::class);
    }

    /**
     * Reclamaciones / disputas recibidas por el taller (1:N).
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }
}
