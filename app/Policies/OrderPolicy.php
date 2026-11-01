<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderShop;
use App\Models\User;

class OrderPolicy
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
     * Visualización general de pedidos:
     * El cliente ve su compra completa.
     * El artesano solo tiene acceso si participa con un subpedido en esa compra.
     */
    public function view(User $user, Order $order): bool
    {
        // 1. El cliente titular de la compra
        if ($user->id === $order->customer_id) {
            return true;
        }

        // 2. Personal de moderación
        if ($user->hasRole('moderator')) {
            return true;
        }

        // 3. El artesano: solo si uno de los subpedidos pertenece a su taller
        if ($user->hasRole('artisan') && $user->shop) {
            return $order->orderShops()->where('shop_id', $user->shop->id)->exists();
        }

        return false;
    }

    /**
     * Solo usuarios autenticados con rol cliente pueden tramitar pedidos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    /**
     * Cancelación por parte del comprador:
     * Solo permitida si el pedido está en estado inicial ('pending' o 'paid'),
     * nunca si los artesanos ya han iniciado la preparación o envío.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->id !== $order->customer_id) {
            return false;
        }

        return in_array($order->status, ['pending', 'paid'], true);
    }

    /**
     * Aislamiento por subpedido (OrderShop):
     * Un artesano SOLO puede gestionar, preparar o actualizar el subpedido de SU taller.
     */
    public function manageSuborder(User $user, OrderShop $orderShop): bool
    {
        if (! $user->hasRole('artisan') || ! $user->shop) {
            return false;
        }

        return $user->shop->id === $orderShop->shop_id;
    }

    /**
     * El repartidor solo puede gestionar envíos y actualizar estados de transporte.
     */
    public function updateDeliveryStatus(User $user, Order $order): bool
    {
        return $user->hasRole('courier');
    }
}
