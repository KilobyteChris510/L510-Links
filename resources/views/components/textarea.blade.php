@props(['name', 'label', 'value' => null, 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($hint) aria-describedby="{{ $name }}-hint" @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->merge(['rows' => 3, 'class' => 'mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200']) }}
    >{{ $value }}</textarea>
    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
