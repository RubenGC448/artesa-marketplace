<?php

namespace App\Policies;

use App\Models\Order;
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
     * Listado de pedidos: cualquiera con permiso orders.view.
     * Qué pedidos ve cada uno lo decide el scope Order::visibleTo().
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('orders.view');
    }

    /**
     * Visualización de un pedido:
     *  - el cliente titular ve su compra completa;
     *  - el moderador ve todos;
     *  - el artesano solo si su taller tiene un subpedido en esa compra.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->id === $order->customer_id) {
            return true;
        }

        if ($user->hasRole('moderator')) {
            return true;
        }

        if ($user->hasRole('artisan') && $user->shop) {
            return $order->orderShops()->where('shop_id', $user->shop->id)->exists();
        }

        return false;
    }

    /**
     * Solo usuarios con permiso de compra (rol cliente) pueden tramitar pedidos.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('orders.create');
    }

    /**
     * Cancelación por parte del comprador: solo su pedido y solo en estado
     * inicial (pending o paid), nunca si los talleres ya preparan o enviaron.
     */
    public function cancel(User $user, Order $order): bool
    {
        if (! $user->checkPermissionTo('orders.cancel') || $user->id !== $order->customer_id) {
            return false;
        }

        return $order->isCancellable();
    }

    /**
     * El repartidor actualiza estados de transporte.
     */
    public function updateDeliveryStatus(User $user, Order $order): bool
    {
        return $user->hasRole('courier') && $user->checkPermissionTo('orders.update_status');
    }
}
