<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesLinkInput;
use App\Models\Link;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreLinkRequest extends FormRequest
{
    use ValidatesLinkInput;

    protected $errorBag = 'createLink';

    public function authorize(): Response
    {
        return Gate::inspect('create', [Link::class, $this->route('group')]);
    }

    /**
     * link_group_id and sort_order are never accepted; they come from the route and server.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
