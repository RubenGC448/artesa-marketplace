<?php

namespace App\Services\Shops;

use App\Models\Shop;

/**
 * Alta (o retirada) de la verificación de un taller.
 * Un taller sin verificar no aparece en el catálogo y su artesano no puede
 * publicar productos (ProductPolicy::create).
 */
class ShopVerificationService
{
    public function verify(Shop $shop): Shop
    {
        $shop->update(['is_verified' => true]);

        return $shop;
    }

    /**
     * Al retirar la verificación, los productos publicados pasan a inactivos
     * para que dejen de venderse.
     */
    public function revoke(Shop $shop): Shop
    {
        $shop->update(['is_verified' => false]);
        $shop->products()->where('status', 'active')->update(['status' => 'inactive']);

        return $shop;
    }
}
