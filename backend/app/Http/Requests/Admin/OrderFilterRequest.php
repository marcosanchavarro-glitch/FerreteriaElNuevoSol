<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrderFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('from') ? ['after_or_equal:from'] : [])],
            'customer' => ['nullable', Rule::in(['registered', 'guest'])], 'page' => ['nullable', 'integer', 'min:1']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->any() && $this->filled('from') && $this->filled('to')
                && CarbonImmutable::parse($this->string('from'))->diffInDays(CarbonImmutable::parse($this->string('to'))) > 366) {
                $validator->errors()->add('to', 'Elegí un rango de hasta 366 días.');
            }
        }];
    }

    public function messages(): array
    {
        return ['to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'from.date_format' => 'Ingresá una fecha inicial válida.', 'to.date_format' => 'Ingresá una fecha final válida.'];
    }
}
