<x-layouts.app title="Dashboard">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Link Management</h1>
            <p class="mt-1 text-sm text-slate-600">Signed in as <span class="font-medium text-slate-900">{{ $user->name }}</span></p>
        </div>
        <a href="{{ route('groups.create') }}" class="inline-flex justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
            Create Link Group
        </a>
    </div>

    <section class="mt-8" aria-labelledby="link-groups-heading">
        <div class="flex items-baseline justify-between">
            <h2 id="link-groups-heading" class="text-base font-semibold">Link groups</h2>
            @if ($groups->total() > 0)
                <p class="text-sm text-slate-500">{{ $groups->total() }} {{ Str::plural('group', $groups->total()) }}</p>
            @endif
        </div>

        @if ($groups->isEmpty())
            <div class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-900">No link groups yet</p>
                <p class="mt-1 text-sm text-slate-500">Create a group to get a permanent URL you can program onto an NFC card.</p>
                <a href="{{ route('groups.create') }}" class="mt-4 inline-flex rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
                    Create your first link group
                </a>
            </div>
        @else
            <ul class="mt-3 space-y-4" role="list">
                @foreach ($groups as $group)
                    <li class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <h3 class="text-base font-semibold break-all">
                                <a href="{{ route('groups.edit', $group) }}" class="hover:underline">{{ $group->name }}</a>
                            </h3>
                            <x-status-badge :active="$group->is_active" />
                            <span class="text-xs text-slate-500">{{ $group->links_count }} {{ Str::plural('link', $group->links_count) }}</span>
                        </div>

                        @if ($group->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $group->description }}</p>
                        @endif

                        <x-copy-url class="mt-3" :url="$group->publicUrl()" :label="'Public URL for '.$group->name" />
                        <p class="mt-1 text-xs text-slate-500">Public page coming in a later milestone.</p>

                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-100 pt-3">
                            <a href="{{ route('groups.edit', $group) }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">Edit</a>

                            <form method="POST" action="{{ route('groups.update', $group) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $group->is_active ? 0 : 1 }}">
                                <button type="submit" class="text-sm font-medium text-slate-700 hover:text-slate-900">
                                    {{ $group->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <x-delete-group-form :group="$group" />
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $groups->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
