<?php

namespace App\Http\Requests;

use App\Enums\HomepageSectionType;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Rules\InternalLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    public function rules(): array
    {
        $banner = $this->route('kind') === 'banners';
        $model = $banner ? Banner::class : HomepageSection::class;
        $needsConfirmation = $this->route('id') && ! $this->boolean('active')
            && $model::whereKey($this->route('id'))->where('active', true)->exists();

        return [
            'type' => $banner ? ['prohibited'] : ['required', Rule::enum(HomepageSectionType::class)],
            'title' => ['required', 'string', 'max:160'], 'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => $banner ? ['prohibited'] : ['nullable', 'string', 'max:5000'],
            'button_text' => ['nullable', 'required_with:button_url', 'string', 'max:60'],
            'button_url' => ['nullable', 'required_with:button_text', 'string', 'max:255', new InternalLink],
            'active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'max_items' => $banner ? ['prohibited'] : ['nullable', 'integer', 'min:1', 'max:24'],
            'image' => [$banner && ! $this->route('id') ? 'required' : 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=1,min_height=1,max_width=3000,max_height=3000'],
            'confirm_deactivate' => $needsConfirmation ? ['required', 'accepted'] : ['sometimes', 'accepted'],
            'image_path' => ['prohibited'], 'settings' => ['prohibited'], 'id' => ['prohibited'],
            'starts_at' => ['prohibited'], 'ends_at' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'título', 'subtitle' => 'subtítulo', 'button_text' => 'texto del botón', 'button_url' => 'enlace', 'image' => 'imagen', 'max_items' => 'cantidad máxima', 'sort_order' => 'orden'];
    }

    public function messages(): array
    {
        return ['required' => 'Completá :attribute.', 'required_with' => 'Completá el texto y el enlace del botón juntos.', 'max.string' => ':attribute no puede superar :max caracteres.', 'max.numeric' => ':attribute no puede superar :max.', 'min.numeric' => ':attribute debe ser al menos :min.', 'integer' => ':attribute debe ser un número entero.', 'prohibited' => 'No se permite modificar :attribute.', 'image.*' => 'Usá una imagen JPG, PNG o WebP válida, de hasta 5 MB y 3000 × 3000 píxeles.', 'confirm_deactivate.accepted' => 'Confirmá que querés ocultar el contenido.', 'confirm_deactivate.required' => 'Confirmá que querés ocultar el contenido.'];
    }
}
