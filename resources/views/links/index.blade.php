@php
    $creating = $errors->createLink->any();
    $editingId = $errors->updateLink->any() ? (int) old('link_id') : null;
@endphp

<x-layouts.app :title="'Links · '.$group->name">
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
        <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>
        <span class="flex gap-4">
            <a href="{{ route('groups.share.show', $group) }}" class="font-medium text-slate-700 hover:text-slate-900">Share</a>
            <a href="{{ route('groups.appearance.edit', $group) }}" class="font-medium text-slate-700 hover:text-slate-900">Appearance</a>
            <a href="{{ route('groups.edit', $group) }}" class="font-medium text-slate-700 hover:text-slate-900">Group settings</a>
        </span>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight break-all">{{ $group->name }}</h1>
        <x-status-badge :active="$group->is_active" />
    </div>

    <div class="mt-3">
        <x-copy-url :url="$group->publicUrl()" />
        <x-public-page-note class="mt-1" :group="$group" />
    </div>

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="add-link-heading">
        <h2 id="add-link-heading" class="text-base font-semibold">Add a link</h2>

        <form method="POST" action="{{ route('groups.links.store', $group) }}" class="mt-4 space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="title" id="new-title" label="Title" bag="createLink" :value="$creating ? old('title') : ''" required maxlength="255" :autofocus="session('status') === 'Link added.'" />
                <x-input name="url" id="new-url" label="URL" bag="createLink" :value="$creating ? old('url') : ''" required maxlength="2048" inputmode="url" placeholder="https://example.com" />
            </div>

            <x-textarea name="description" id="new-description" label="Description (optional)" bag="createLink" :value="$creating ? old('description') : ''" maxlength="1000" rows="2" />

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <label for="new-is-active" class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input id="new-is-active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300" @checked($creating ? old('is_active') : true)>
                    Active
                </label>
                <x-button class="sm:w-auto">Add link</x-button>
            </div>
        </form>
    </section>

    <section class="mt-8" aria-labelledby="links-heading">
        <div class="flex items-baseline justify-between">
            <h2 id="links-heading" class="text-base font-semibold">Links</h2>
            @if ($links->isNotEmpty())
                <p class="text-sm text-slate-500">{{ $links->count() }} {{ Str::plural('link', $links->count()) }}</p>
            @endif
        </div>

        @if ($links->isEmpty())
            <div class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-900">No links yet</p>
                <p class="mt-1 text-sm text-slate-500">Add your first link above. Links appear on the public page in the order shown here.</p>
            </div>
        @else
            <div x-data="linkSorter(@js(route('groups.links.order', $group)))">
                <p class="mt-1 text-xs text-slate-500">Drag the handle, or use Move up and Move down, to change the order.</p>
                <p class="mt-2 min-h-5 text-sm" role="status" aria-live="polite" x-text="status" x-bind:class="failed ? 'text-red-600' : 'text-emerald-700'"></p>

                <ul x-ref="list" class="mt-1 space-y-3" role="list">
                    @foreach ($links as $link)
                        @php($editing = $editingId === $link->id)
                        <li id="link-{{ $link->id }}" data-id="{{ $link->id }}" class="group rounded-xl border border-slate-200 bg-white" x-data="{ editing: @js($editing) }">
                            <div class="flex gap-3 p-4">
                                <button type="button" data-sort-handle class="mt-0.5 h-8 w-8 shrink-0 cursor-grab touch-none rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-600 active:cursor-grabbing" aria-label="Drag to reorder {{ $link->title }}" title="Drag to reorder">
                                    <svg class="mx-auto h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/></svg>
                                </button>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <h3 class="font-semibold break-words {{ $link->is_active ? '' : 'text-slate-500' }}">{{ $link->title }}</h3>
                                        <x-status-badge :active="$link->is_active" />
                                    </div>
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block truncate text-sm text-slate-600 underline decoration-slate-300 hover:text-slate-900">{{ $link->url }}</a>
                                    @if ($link->description)
                                        <p class="mt-1 text-sm text-slate-600">{{ $link->description }}</p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                        <button type="button" class="font-medium text-slate-700 hover:text-slate-900" x-on:click="editing = ! editing" x-bind:aria-expanded="editing" aria-controls="edit-link-{{ $link->id }}">Edit</button>

                                        <form method="POST" action="{{ route('groups.links.update', [$group, $link]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $link->is_active ? 0 : 1 }}">
                                            <button type="submit" class="font-medium text-slate-700 hover:text-slate-900">{{ $link->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>

                                        <form method="POST" action="{{ route('groups.links.destroy', [$group, $link]) }}" x-data x-on:submit="if (! confirm('Delete this link? This cannot be undone.')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 hover:text-red-800">Delete</button>
                                        </form>

                                        <span class="ml-auto flex gap-1">
                                            <button type="button" x-on:click="move($el, -1)" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50 group-first:opacity-40">
                                                <span aria-hidden="true">&uarr;</span><span class="sr-only">Move {{ $link->title }} up</span>
                                            </button>
                                            <button type="button" x-on:click="move($el, 1)" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50 group-last:opacity-40">
                                                <span aria-hidden="true">&darr;</span><span class="sr-only">Move {{ $link->title }} down</span>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <form id="edit-link-{{ $link->id }}" method="POST" action="{{ route('groups.links.update', [$group, $link]) }}" class="space-y-4 border-t border-slate-100 p-4" x-show="editing" x-cloak>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="link_id" value="{{ $link->id }}">

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <x-input name="title" :id="'title-'.$link->id" label="Title" :bag="$editing ? 'updateLink' : 'none'" :value="$editing ? old('title') : $link->title" required maxlength="255" />
                                    <x-input name="url" :id="'url-'.$link->id" label="URL" :bag="$editing ? 'updateLink' : 'none'" :value="$editing ? old('url') : $link->url" required maxlength="2048" inputmode="url" />
                                </div>

                                <x-textarea name="description" :id="'description-'.$link->id" label="Description (optional)" :bag="$editing ? 'updateLink' : 'none'" :value="$editing ? old('description') : $link->description" maxlength="1000" rows="2" />

                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <label for="is-active-{{ $link->id }}" class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="hidden" name="is_active" value="0">
                                        <input id="is-active-{{ $link->id }}" name="is_active" type="checkbox" value="1" class="rounded border-slate-300" @checked($editing ? old('is_active') : $link->is_active)>
                                        Active
                                    </label>
                                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                                        <button type="button" class="text-sm font-medium text-slate-600 hover:text-slate-900" x-on:click="editing = false">Cancel</button>
                                        <x-button class="sm:w-auto">Save link</x-button>
                                    </div>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
</x-layouts.app>
