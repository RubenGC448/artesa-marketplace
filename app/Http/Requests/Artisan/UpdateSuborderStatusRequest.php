<?php

namespace App\Http\Requests\Artisan;

use App\Models\OrderShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSuborderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSuborder', $this->route('orderShop'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(OrderShop::ARTISAN_STATUSES)],
        ];
    }
}
