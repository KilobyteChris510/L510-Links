<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesLinkInput;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateLinkRequest extends FormRequest
{
    use ValidatesLinkInput;

    protected $errorBag = 'updateLink';

    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('link'));
    }

    /**
     * Fields are optional so the enable/disable toggle can submit is_active alone.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'url' => ['sometimes', 'required', 'string', 'max:2048', 'url:http,https'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
