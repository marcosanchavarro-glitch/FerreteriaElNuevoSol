<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(OrderStatus::class)],
            'expected_status' => ['required', Rule::enum(OrderStatus::class)],
            'confirm_cancel' => $this->input('status') === 'cancelled' ? ['required', 'accepted'] : ['prohibited']];
    }

    public function messages(): array
    {
        return ['confirm_cancel.required' => 'Confirmá la cancelación del pedido.',
            'confirm_cancel.accepted' => 'Confirmá la cancelación del pedido.'];
    }
}
