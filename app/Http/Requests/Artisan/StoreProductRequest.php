<?php

namespace App\Http\Requests\Artisan;

use App\Models\Product;

class StoreProductRequest extends ProductRequest
{
    /**
     * Solo un artesano con permiso products.create y taller verificado.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }
}
