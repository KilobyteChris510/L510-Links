<?php

namespace App\Http\Controllers;

use App\Models\LinkGroup;
use Illuminate\View\View;

class PublicLinkGroupController extends Controller
{
    /**
     * Inactive, soft-deleted (excluded by the SoftDeletes scope), and unknown groups all 404.
     */
    public function __invoke(string $slug): View
    {
        $group = LinkGroup::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['links' => fn ($query) => $query->where('is_active', true)])
            ->firstOrFail();

        return view('public.show', ['group' => $group]);
    }
}
