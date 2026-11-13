@props(['name', 'label', 'type' => 'text', 'id' => null, 'bag' => 'default'])

@php($id ??= $name)

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @error($name, $bag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200']) }}
    >
    @error($name, $bag)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
