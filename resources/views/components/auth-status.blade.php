@if (session('status'))
    <div
        x-data="{ show: true }"
        x-show="show"
        class="mb-4 flex items-start justify-between gap-3 rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
        role="status"
    >
        <span>{{ session('status') }}</span>
        <button type="button" x-on:click="show = false" class="text-emerald-700 hover:text-emerald-900" aria-label="Dismiss">&times;</button>
    </div>
@endif
