<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateDeliveryStatus', $this->route('shipment')->orderShop->order);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['in_transit', 'delivered', 'failed'])],
            'carrier' => ['nullable', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:60', 'required_if:status,in_transit'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => __('estado'),
            'carrier' => __('transportista'),
            'tracking_number' => __('código de seguimiento'),
        ];
    }
}
