@props(['group'])

@if ($group->is_active)
    <p {{ $attributes->merge(['class' => 'text-xs text-slate-500']) }}>
        <a href="{{ $group->publicUrl() }}" target="_blank" rel="noopener" class="font-medium text-slate-700 underline decoration-slate-300 hover:text-slate-900">View public page<span class="sr-only"> (opens in a new tab)</span></a>
    </p>
@else
    <p {{ $attributes->merge(['class' => 'text-xs text-slate-500']) }}>
        Inactive: this URL shows a "not found" page until the group is activated.
    </p>
@endif
