@props(['name', 'label', 'value' => null, 'hint' => null, 'id' => null, 'bag' => 'default'])

@php($id ??= $name)

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        @error($name, $bag) aria-invalid="true" aria-errormessage="{{ $id }}-error" @enderror
        {{ $attributes->merge(['rows' => 3, 'class' => 'mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200']) }}
    >{{ $value }}</textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name, $bag)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
