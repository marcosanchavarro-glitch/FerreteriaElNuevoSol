<?php

namespace App\Http\Requests;

use App\Services\Content\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return ['business_name' => ['required', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9 ()-]+$/D'], 'whatsapp_number' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'], 'pickup_address' => ['nullable', 'string', 'max:255'], 'pickup_hours' => ['nullable', 'string', 'max:255']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), [...array_keys(SiteSettings::FIELDS), '_token', '_method'])) {
                $validator->errors()->add('settings', 'La solicitud contiene opciones no permitidas.');
            }
        }];
    }
}
