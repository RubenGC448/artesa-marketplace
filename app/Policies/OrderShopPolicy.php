<?php

namespace App\Policies;

use App\Models\OrderShop;
use App\Models\User;

/**
 * Autorización sobre el subpedido de un taller dentro de un pedido.
 * Aísla a cada artesano: solo ve y gestiona la parte de SU taller.
 */
class OrderShopPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, OrderShop $orderShop): bool
    {
        if ($user->hasRole('moderator') || $user->id === $orderShop->order->customer_id) {
            return true;
        }

        return $this->ownsSuborder($user, $orderShop);
    }

    /**
     * Preparar, marcar listo, enviar o cancelar la parte de su taller.
     */
    public function manageSuborder(User $user, OrderShop $orderShop): bool
    {
        return $user->hasRole('artisan')
            && $user->checkPermissionTo('orders.update_status')
            && $this->ownsSuborder($user, $orderShop);
    }

    private function ownsSuborder(User $user, OrderShop $orderShop): bool
    {
        return $user->shop !== null && (int) $user->shop->id === (int) $orderShop->shop_id;
    }
}
