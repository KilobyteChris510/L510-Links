<x-layouts.app title="Dashboard">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Link Management</h1>
        <p class="mt-1 text-sm text-slate-600">Signed in as <span class="font-medium text-slate-900">{{ $user->name }}</span></p>
    </div>

    <section class="mt-8 rounded-xl border border-slate-200 bg-white" aria-labelledby="link-groups-heading">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 id="link-groups-heading" class="text-base font-semibold">Link groups</h2>
        </div>
        <div class="px-5 py-12 text-center">
            <p class="text-sm font-medium text-slate-900">No link groups yet</p>
            <p class="mt-1 text-sm text-slate-500">Link groups will appear here once they are available.</p>
        </div>
    </section>
</x-layouts.app>
