<?php

namespace App\Http\Requests;

use App\Models\Link;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderLinksRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('reorder', [Link::class, $this->route('group')]);
    }

    /**
     * The payload must list every link in the group exactly once: the size, distinct,
     * and in rules together reject missing, duplicate, and foreign IDs.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $ids = $this->route('group')->links()->pluck('id')->all();

        return [
            'links' => ['required', 'array', 'size:'.count($ids)],
            'links.*' => ['required', 'integer', 'distinct', Rule::in($ids)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'links.size' => 'The order must include every link in this group exactly once.',
            'links.*.distinct' => 'The order contains the same link more than once.',
            'links.*.in' => 'The order contains a link that does not belong to this group.',
        ];
    }
}
