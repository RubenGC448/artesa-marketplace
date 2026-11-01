<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
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
     * Catálogo público visible para todos.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Si la pieza está publicada, es visible públicamente.
     * Si está en borrador o archivada, solo el artesano titular del taller puede verla.
     */
    public function view(?User $user, Product $product): bool
    {
        if ($product->status === 'published') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return ($user->shop && $user->shop->id === $product->shop_id) || $user->hasRole('moderator');
    }

    /**
     * Solo un artesano con taller propio verificado puede crear productos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('artisan')
            && $user->shop !== null
            && $user->shop->is_verified;
    }

    /**
     * Aislamiento estricto de catálogo:
     * Solo el artesano dueño del taller al que pertenece el producto puede modificarlo.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->shop !== null && $user->shop->id === $product->shop_id;
    }

    /**
     * Solo el artesano dueño del taller puede eliminar o enviar a borrado lógico la pieza.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->shop !== null && $user->shop->id === $product->shop_id;
    }
}
