<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
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
     * Reseña verificada:
     * Solo un cliente que haya comprado este producto en un pedido ENTREGADO ('delivered')
     * y que no haya emitido ya una valoración sobre la misma pieza puede reseñar.
     */
    public function create(User $user, Product $product): bool
    {
        // 1. Debe tener rol customer
        if (! $user->hasRole('customer')) {
            return false;
        }

        // 2. No debe haber valorado ya este producto
        $alreadyReviewed = Review::where('customer_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($alreadyReviewed) {
            return false;
        }

        // 3. Comprobar que existe una compra con estado 'delivered' que incluya este producto
        return $user->orders()
            ->where('status', 'delivered')
            ->whereHas('orderProducts', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->exists();
    }

    /**
     * Réplica del artesano:
     * Solo el artesano dueño del taller creador de la pieza puede responder a la reseña.
     */
    public function reply(User $user, Review $review): bool
    {
        if (! $user->hasRole('artisan') || ! $user->shop) {
            return false;
        }

        return $user->shop->id === $review->product->shop_id;
    }

    /**
     * Moderación y borrado:
     * El autor puede borrarla, o el moderador/administrador de la plataforma.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->customer_id || $user->hasRole('moderator');
    }
}
