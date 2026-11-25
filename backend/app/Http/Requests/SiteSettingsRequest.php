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
        return ['business_name' => ['required', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9 ()-]+$/D'], 'whatsapp_number' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'], 'whatsapp_inquiries_number' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'], 'contact_primary_number' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'], 'contact_secondary_number' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'], 'contact_tertiary_number' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/D'], 'whatsapp_orders_contact' => ['sometimes', 'required', 'in:custom,primary,secondary,tertiary'], 'whatsapp_inquiries_contact' => ['sometimes', 'required', 'in:custom,primary,secondary,tertiary'], 'whatsapp_number_kind' => ['sometimes', 'required', 'in:pending,temporary,business'], 'whatsapp_inquiries_kind' => ['sometimes', 'required', 'in:pending,temporary,business'], 'pickup_address' => ['nullable', 'string', 'max:255'], 'locality' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'postal_code' => ['nullable', 'string', 'max:20', 'regex:/^[\pL\pN -]+$/uD'], 'maps_embed_url' => ['nullable', 'string', 'max:4096', 'url:https', 'regex:'.SiteSettings::MAPS_EMBED_PATTERN], 'maps_url' => ['nullable', 'string', 'max:2048', 'url:https', 'regex:~^https://(?:(?:www\.)?google\.com(?:\.ar)?/maps(?:[/?#]|$)|maps\.google\.com/|maps\.app\.goo\.gl/|goo\.gl/maps/)[^\s]*$~D'], 'pickup_hours' => ['nullable', 'string', 'max:255']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), [...array_keys(SiteSettings::FIELDS), '_token', '_method'])) {
                $validator->errors()->add('settings', 'La solicitud contiene opciones no permitidas.');
            }
            $values = array_replace(app(SiteSettings::class)->all(), $this->only(array_keys(SiteSettings::FIELDS)));
            if (is_string($values['maps_url'])) {
                parse_str(parse_url($values['maps_url'], PHP_URL_QUERY) ?: '', $query);
                $queryKeys = array_map(fn ($key) => strtolower((string) $key), array_keys($query));
                if (array_intersect($queryKeys, ['key', 'api_key', 'apikey', 'token', 'access_token', 'signature'])) {
                    $validator->errors()->add('maps_url', 'El enlace no debe contener claves de API, firmas ni tokens. Usá Compartir en Google Maps.');
                }
            }
            if (is_string($values['maps_embed_url']) && $values['maps_embed_url'] !== '') {
                foreach (['pickup_address', 'locality', 'province', 'maps_url'] as $field) {
                    if (! is_string($values[$field]) || trim($values[$field]) === '') {
                        $validator->errors()->add($field, 'Completá la ubicación confirmada y el enlace de Maps antes de habilitar el mapa integrado.');
                    }
                }
            }
            foreach (['whatsapp_orders_contact', 'whatsapp_inquiries_contact'] as $field) {
                $choice = $values[$field];
                if (in_array($choice, ['primary', 'secondary', 'tertiary'], true)) {
                    $number = $values['contact_'.$choice.'_number'];
                    if (! is_string($number) || $number === '') {
                        $validator->errors()->add($field, 'Cargá el número elegido en la agenda, o seleccioná Otro número.');
                    }
                }
            }
        }];
    }

    public function messages(): array
    {
        $messages = ['maps_url.*' => 'Ingresá un enlace HTTPS válido de Google Maps.', 'maps_embed_url.*' => 'Pegá solamente el enlace src de Compartir → Insertar un mapa de Google Maps, sin código HTML ni claves de API.', 'postal_code.*' => 'Ingresá un código postal de hasta 20 caracteres, con letras, números, espacios o guiones.', 'business_name.required' => 'Completá el nombre comercial.'];
        foreach (['whatsapp_number', 'whatsapp_inquiries_number', 'contact_primary_number', 'contact_secondary_number', 'contact_tertiary_number'] as $field) {
            $messages[$field.'.regex'] = 'Ingresá código de país y número, solo dígitos y sin cero inicial.';
        }

        return $messages;
    }
}
