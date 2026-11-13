<x-layouts.app :title="'Edit '.$group->name">
    <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight break-all">{{ $group->name }}</h1>
        <x-status-badge :active="$group->is_active" />
    </div>

    <div class="mt-4">
        <a href="{{ route('groups.links.index', $group) }}" class="inline-flex justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">Manage links</a>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="public-url-heading">
        <h2 id="public-url-heading" class="text-base font-semibold">Permanent public URL</h2>
        <p class="mt-1 text-sm text-slate-600">This URL never changes, even if you rename the group, so it is safe to program onto NFC cards.</p>
        <x-copy-url class="mt-3" :url="$group->publicUrl()" />
        <p class="mt-2 text-xs text-slate-500">The public page is not available yet; it will be added in a later milestone.</p>
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="details-heading">
        <h2 id="details-heading" class="text-base font-semibold">Details</h2>

        <form method="POST" action="{{ route('groups.update', $group) }}" class="mt-4">
            @method('PUT')
            @include('groups._form')

            <div class="mt-6 flex justify-end">
                <x-button class="sm:w-auto">Save changes</x-button>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-xl border border-red-200 bg-white p-5 sm:p-6" aria-labelledby="delete-heading">
        <h2 id="delete-heading" class="text-base font-semibold text-red-700">Delete link group</h2>
        <p class="mt-1 text-sm text-slate-600">The group is removed from your dashboard. Its links are kept and its public URL stays reserved.</p>
        <x-delete-group-form :group="$group" class="mt-4 rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">
            Delete link group
        </x-delete-group-form>
    </section>
</x-layouts.app>
