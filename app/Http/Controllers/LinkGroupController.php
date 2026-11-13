<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLinkGroupRequest;
use App\Http\Requests\UpdateLinkGroupRequest;
use App\Models\LinkGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LinkGroupController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', LinkGroup::class);

        return view('groups.create', [
            'group' => new LinkGroup,
        ]);
    }

    public function store(StoreLinkGroupRequest $request): RedirectResponse
    {
        $group = $request->user()->linkGroups()->make($request->validated());
        $group->saveWithUniqueSlug();

        return to_route('groups.edit', $group)->with('status', 'Link group created.');
    }

    public function edit(LinkGroup $group): View
    {
        Gate::authorize('update', $group);

        return view('groups.edit', [
            'group' => $group,
        ]);
    }

    public function update(UpdateLinkGroupRequest $request, LinkGroup $group): RedirectResponse
    {
        $group->update($request->validated());

        $status = $request->has('name')
            ? 'Link group updated.'
            : ($group->is_active ? 'Link group activated.' : 'Link group deactivated.');

        return back(fallback: route('groups.edit', $group))->with('status', $status);
    }

    public function destroy(LinkGroup $group): RedirectResponse
    {
        Gate::authorize('delete', $group);

        $group->delete();

        return to_route('dashboard')->with('status', 'Link group deleted.');
    }
}
