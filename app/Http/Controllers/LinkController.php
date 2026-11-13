<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Link;
use App\Models\LinkGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LinkController extends Controller
{
    public function index(LinkGroup $group): View
    {
        Gate::authorize('viewAny', [Link::class, $group]);

        return view('links.index', [
            'group' => $group,
            'links' => $group->links()->get(),
        ]);
    }

    public function store(StoreLinkRequest $request, LinkGroup $group): RedirectResponse
    {
        $link = $group->links()->make($request->validated());
        $link->sort_order = ($group->links()->reorder()->max('sort_order') ?? -1) + 1;
        $link->save();

        return to_route('groups.links.index', $group)->with('status', 'Link added.');
    }

    public function update(UpdateLinkRequest $request, LinkGroup $group, Link $link): RedirectResponse
    {
        $link->update($request->validated());

        $status = $request->hasAny(['title', 'url', 'description'])
            ? 'Link updated.'
            : ($link->is_active ? 'Link enabled.' : 'Link disabled.');

        return to_route('groups.links.index', $group)->with('status', $status);
    }

    public function destroy(LinkGroup $group, Link $link): RedirectResponse
    {
        Gate::authorize('delete', $link);

        $link->delete();

        return to_route('groups.links.index', $group)->with('status', 'Link deleted.');
    }
}
