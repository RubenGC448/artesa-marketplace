<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    /**
     * El Super Admin tiene autorización automática para cualquier acción.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Cualquier visitante o usuario puede ver el listado de talleres activos.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Visualización de un taller concreto:
     * - Público si está verificado.
     * - Si está pendiente/no verificado: solo el dueño o moderadores.
     */
    public function view(?User $user, Shop $shop): bool
    {
        if ($shop->is_verified) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->id === $shop->user_id || $user->hasRole('moderator');
    }

    /**
     * Solo usuarios con rol 'artisan' que no tengan ya un taller pueden crear uno.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('artisan') && $user->shop === null;
    }

    /**
     * Aislamiento de taller: Solo el artesano titular puede editar su propio taller.
     */
    public function update(User $user, Shop $shop): bool
    {
        return $user->id === $shop->user_id;
    }

    /**
     * Solo el artesano titular puede dar de baja su taller.
     */
    public function delete(User $user, Shop $shop): bool
    {
        return $user->id === $shop->user_id;
    }

    /**
     * Solo el moderador puede verificar y auditar un taller artesano.
     */
    public function verify(User $user, Shop $shop): bool
    {
        return $user->hasRole('moderator');
    }
}
