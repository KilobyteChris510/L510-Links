<?php

namespace App\Http\Requests;

use App\Models\LinkGroup;
use Illuminate\Foundation\Http\FormRequest;

class StoreLinkGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LinkGroup::class);
    }

    /**
     * Only these keys reach the model, so ownership and slug can never be submitted.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
