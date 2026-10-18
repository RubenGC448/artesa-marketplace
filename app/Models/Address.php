<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'street',
        'city',
        'province',
        'postal_code',
        'country',
        'phone',
        'is_default',
    ];

    /**
     * Tipos de datos que deben castearse.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones Eloquent
    |--------------------------------------------------------------------------
    */

    /**
     * Usuario propietario de la dirección (1:N inverso).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedidos realizados donde se usó esta dirección como destino (1:N).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos y Helpers de Dominio
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve la dirección formateada en una sola línea.
     */
    public function getFormattedAddressAttribute(): string
    {
        return "{$this->street}, {$this->postal_code} {$this->city} ({$this->province}), {$this->country}";
    }
}
