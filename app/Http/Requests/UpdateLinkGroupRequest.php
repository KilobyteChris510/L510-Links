<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateLinkGroupRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('group'));
    }

    /**
     * Fields are optional so the dashboard can toggle is_active on its own; any field
     * that is submitted must be valid. Ownership and slug are never accepted.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
