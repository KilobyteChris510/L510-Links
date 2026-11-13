@csrf

<div class="space-y-5">
    <x-input name="name" label="Name" :value="old('name', $group->name)" required maxlength="255" autofocus />

    <x-textarea name="description" label="Description (optional)" :value="old('description', $group->description)" maxlength="1000" hint="Up to 1,000 characters." />

    <div>
        <input type="hidden" name="is_active" value="0">
        <label for="is_active" class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300" @checked(old('is_active', $group->is_active ?? true))>
            Active
        </label>
        <p class="mt-1 text-xs text-slate-500">Inactive groups stay in your dashboard. Public-page behavior for inactive groups arrives with public pages.</p>
        @error('is_active')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
