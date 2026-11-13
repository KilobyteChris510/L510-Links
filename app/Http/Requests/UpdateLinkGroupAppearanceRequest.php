<?php

namespace App\Http\Requests;

use App\Models\LinkGroup;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateLinkGroupAppearanceRequest extends FormRequest
{
    public const AVATAR_MAX_KILOBYTES = 2048;

    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('group'));
    }

    /**
     * Only theme, the known appearance keys, and the avatar fields are accepted;
     * unknown appearance keys fail the array rule instead of being stored.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'theme' => ['required', 'string', Rule::in(array_keys(config('appearance.themes')))],
            'appearance' => ['required', 'array:'.implode(',', LinkGroup::appearanceKeys())],
            'appearance.button_shape' => ['required', 'string', Rule::in(array_keys(config('appearance.button_shapes')))],
            'avatar' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.self::AVATAR_MAX_KILOBYTES,
                'dimensions:min_width=32,min_height=32,max_width=4000,max_height=4000',
                'prohibits:remove_avatar',
            ],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];

        foreach (array_keys(config('appearance.colors')) as $key) {
            $rules['appearance.'.$key] = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appearance.*.regex' => 'The :attribute must be a hex color such as #1a2b3c.',
            'appearance.array' => 'The appearance settings contain an unsupported option.',
            'avatar.prohibits' => 'Choose a new image or remove the current one, not both.',
            'avatar.max' => 'The profile image must be 2 MB or smaller.',
            'avatar.dimensions' => 'The profile image must be between 32 and 4000 pixels on each side.',
            'avatar.mimes' => 'The profile image must be a JPEG, PNG, or WebP file.',
            'avatar.mimetypes' => 'The profile image must be a JPEG, PNG, or WebP file.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['appearance.button_shape' => 'button shape', 'avatar' => 'profile image'];

        foreach (config('appearance.colors') as $key => $label) {
            $attributes['appearance.'.$key] = strtolower($label).' color';
        }

        return $attributes;
    }
}
