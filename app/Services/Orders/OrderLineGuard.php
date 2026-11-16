<?php

namespace App\Services\Orders;

use App\Exceptions\Domain\InvalidOrderLineException;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Garantiza la coherencia de una línea de pedido (corrección del Sprint 2).
 *
 * Cada clave foránea de order_product es válida por separado, pero eso no basta:
 * la base de datos aceptaría una línea cuyo subpedido es de otro pedido, cuyo
 * producto es de otro taller o cuya variante es de otro producto. Esta clase
 * comprueba las tres reglas antes de guardar (se invoca desde OrderProduct::saving).
 */
class OrderLineGuard
{
    /**
     * @throws InvalidOrderLineException
     */
    public function assertConsistent(OrderProduct $line): void
    {
        $orderShop = OrderShop::find($line->order_shop_id);

        if (! $orderShop) {
            throw new InvalidOrderLineException('La línea debe pertenecer a un subpedido existente.');
        }

        // 1. El subpedido pertenece al mismo pedido que la línea
        if ((int) $orderShop->order_id !== (int) $line->order_id) {
            throw new InvalidOrderLineException(
                "El subpedido #{$orderShop->id} no pertenece al pedido #{$line->order_id}."
            );
        }

        // 2. El producto es del taller del subpedido
        $product = Product::withTrashed()->find($line->product_id);

        if (! $product) {
            throw new InvalidOrderLineException('El producto de la línea no existe.');
        }

        if ((int) $product->shop_id !== (int) $orderShop->shop_id) {
            throw new InvalidOrderLineException(
                "El producto «{$product->title}» no pertenece al taller del subpedido #{$orderShop->id}."
            );
        }

        // 3. La variante (si hay) es de ese producto
        if ($line->product_variant_id !== null) {
            $variant = ProductVariant::find($line->product_variant_id);

            if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                throw new InvalidOrderLineException(
                    "La variante #{$line->product_variant_id} no pertenece al producto «{$product->title}»."
                );
            }
        }
    }
}
