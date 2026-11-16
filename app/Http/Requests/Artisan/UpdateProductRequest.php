<?php

namespace App\Http\Requests\Artisan;

class UpdateProductRequest extends ProductRequest
{
    /**
     * Solo el artesano dueño del taller del producto.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }
}
