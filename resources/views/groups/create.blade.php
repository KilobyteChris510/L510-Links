<x-layouts.app title="Create link group">
    <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>

    <h1 class="mt-4 text-2xl font-semibold tracking-tight">Create link group</h1>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        <form method="POST" action="{{ route('groups.store') }}">
            @include('groups._form')

            <div class="mt-6 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
                The public URL is generated from the name when you create the group and can never be changed afterwards, even if you rename it.
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('dashboard') }}" class="text-center text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
                <x-button class="sm:w-auto">Create link group</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
