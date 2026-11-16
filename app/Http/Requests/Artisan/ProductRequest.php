<?php

namespace App\Http\Requests\Artisan;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas comunes de alta y edición de productos del taller.
 */
abstract class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'featured' => $this->boolean('featured'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['required', 'numeric', 'min:0.50', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:10000'],
            'tax_rate' => ['required', Rule::in(array_keys(config('artesa.tax_rates')))],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'featured' => ['boolean'],
            'available_from' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('título'),
            'category_id' => __('categoría'),
            'description' => __('descripción'),
            'base_price' => __('precio'),
            'stock' => __('stock'),
            'tax_rate' => __('tipo de IVA'),
            'status' => __('estado'),
            'featured' => __('destacado'),
            'available_from' => __('disponible desde'),
        ];
    }

    public function messages(): array
    {
        return [
            'available_from.after_or_equal' => __('La fecha de disponibilidad no puede ser anterior a hoy.'),
            'base_price.min' => __('El precio mínimo de una pieza es 0,50 €.'),
        ];
    }
}
