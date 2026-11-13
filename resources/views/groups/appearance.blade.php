@php
    $saved = $group->appearanceSettings();
    $theme = old('theme', $group->appearanceTheme());
    $values = [];
    foreach ($saved as $key => $value) {
        $values[$key] = old('appearance.'.$key, $value);
    }
    $themes = config('appearance.themes');
    $shapes = config('appearance.button_shapes');
    $avatarUrl = $group->avatarUrl();
@endphp

<x-layouts.app :title="'Appearance · '.$group->name">
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
        <a href="{{ route('groups.links.index', $group) }}" class="text-slate-600 hover:text-slate-900">&larr; Back to links</a>
        <a href="{{ route('groups.edit', $group) }}" class="font-medium text-slate-700 hover:text-slate-900">Group settings</a>
    </div>

    <h1 class="mt-4 text-2xl font-semibold tracking-tight break-words">Appearance: {{ $group->name }}</h1>
    <p class="mt-1 text-sm text-slate-600">Changes appear in the preview right away and on the public page after you save. The public URL never changes.</p>

    <div
        class="mt-6 grid gap-8 lg:grid-cols-2 lg:items-start"
        x-data="appearanceEditor(@js([
            'themes' => $themes,
            'shapes' => $shapes,
            'theme' => $theme,
            'values' => $values,
            'avatar' => $avatarUrl,
            'removeAvatar' => (bool) old('remove_avatar'),
            'avatarMaxBytes' => App\Http\Requests\UpdateLinkGroupAppearanceRequest::AVATAR_MAX_KILOBYTES * 1024,
        ]))"
    >
        <form method="POST" action="{{ route('groups.appearance.update', $group) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    The appearance was not saved. Please correct the highlighted fields.
                    @error('appearance') <p class="mt-1">{{ $message }}</p> @enderror
                </div>
            @endif

            <fieldset class="rounded-xl border border-slate-200 bg-white p-5">
                <legend class="px-1 text-base font-semibold">Theme</legend>
                <p class="text-sm text-slate-600">Choosing a theme resets the colors and button shape to that theme's defaults.</p>
                <div class="mt-3 grid grid-cols-3 gap-3">
                    @foreach ($themes as $key => $definition)
                        <label class="cursor-pointer rounded-lg border border-slate-200 p-2 text-center text-sm font-medium has-checked:border-slate-900 has-checked:ring-2 has-checked:ring-slate-900 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-slate-500">
                            <input type="radio" name="theme" value="{{ $key }}" class="sr-only" x-model="theme" x-on:change="applyTheme('{{ $key }}')" @checked($theme === $key)>
                            <span class="flex h-12 flex-col items-center justify-center gap-1 rounded-md" style="background: {{ $definition['background_color'] }}" aria-hidden="true">
                                <span class="block h-2 w-10 rounded-full" style="background: {{ $definition['text_color'] }}"></span>
                                <span class="block h-3 w-14 rounded-full" style="background: {{ $definition['button_background_color'] }}; outline: 1px solid {{ $definition['text_color'] }}22"></span>
                            </span>
                            <span class="mt-2 block">{{ $definition['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('theme') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-3 text-sm text-slate-700" role="status" aria-live="polite" x-text="notice"></p>
            </fieldset>

            <fieldset class="rounded-xl border border-slate-200 bg-white p-5">
                <legend class="px-1 text-base font-semibold">Colors</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (config('appearance.colors') as $key => $label)
                        <div>
                            <label for="color-{{ $key }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input type="color" x-model="values.{{ $key }}" value="{{ $values[$key] }}" aria-label="{{ $label }} color picker" class="h-10 w-12 shrink-0 cursor-pointer rounded-md border border-slate-300 bg-white p-1">
                                <input
                                    id="color-{{ $key }}"
                                    type="text"
                                    name="appearance[{{ $key }}]"
                                    value="{{ $values[$key] }}"
                                    x-model="values.{{ $key }}"
                                    required
                                    maxlength="7"
                                    pattern="#[0-9a-fA-F]{6}"
                                    spellcheck="false"
                                    autocomplete="off"
                                    aria-describedby="color-hint"
                                    @error('appearance.'.$key) aria-invalid="true" @enderror
                                    class="block w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm uppercase shadow-xs focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                >
                            </div>
                            @error('appearance.'.$key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
                <p id="color-hint" class="mt-3 text-xs text-slate-500">Use the picker or type a hex value such as #1A2B3C.</p>

                <div class="mt-4 space-y-2" role="status" aria-live="polite">
                    <template x-for="warning in warnings" :key="warning">
                        <p class="flex gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            <span aria-hidden="true">&#9888;</span><span><span class="font-semibold">Low contrast:</span> <span x-text="warning"></span></span>
                        </p>
                    </template>
                </div>
            </fieldset>

            <fieldset class="rounded-xl border border-slate-200 bg-white p-5">
                <legend class="px-1 text-base font-semibold">Button shape</legend>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ($shapes as $key => $shape)
                        <label class="cursor-pointer rounded-lg border border-slate-200 px-2 py-3 text-center text-sm font-medium has-checked:border-slate-900 has-checked:ring-2 has-checked:ring-slate-900 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-slate-500">
                            <input type="radio" name="appearance[button_shape]" value="{{ $key }}" class="sr-only" x-model="values.button_shape" @checked($values['button_shape'] === $key)>
                            <span class="mx-auto block h-6 w-16 border-2 border-slate-400 bg-slate-100" style="border-radius: {{ $shape['radius'] }}" aria-hidden="true"></span>
                            <span class="mt-2 block">{{ $shape['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('appearance.button_shape') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="rounded-xl border border-slate-200 bg-white p-5">
                <legend class="px-1 text-base font-semibold">Profile image</legend>
                <div class="flex items-center gap-4">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Current profile image" class="h-16 w-16 shrink-0 rounded-full object-cover ring-1 ring-slate-200">
                    @else
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500">None</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <label for="avatar" class="block text-sm font-medium text-slate-700">{{ $avatarUrl ? 'Replace image' : 'Upload image' }}</label>
                        <input
                            id="avatar"
                            name="avatar"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            aria-describedby="avatar-hint"
                            x-on:change="pickAvatar($event)"
                            @error('avatar') aria-invalid="true" @enderror
                            class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-700"
                        >
                    </div>
                </div>
                <p id="avatar-hint" class="mt-2 text-xs text-slate-500">JPEG, PNG, or WebP, up to 2 MB. Images are cropped to a centered square.</p>
                <p class="mt-1 text-sm text-red-600" role="alert" x-show="avatarError" x-text="avatarError" x-cloak></p>
                @error('avatar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($avatarUrl)
                    <label for="remove_avatar" class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                        <input id="remove_avatar" name="remove_avatar" type="checkbox" value="1" class="rounded border-slate-300" x-model="removeAvatar" x-on:change="refreshAvatar()" @checked(old('remove_avatar'))>
                        Remove current image
                    </label>
                @endif
            </fieldset>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('groups.links.index', $group) }}" class="text-center text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
                <x-button class="sm:w-auto">Save changes</x-button>
            </div>
        </form>

        <section class="lg:sticky lg:top-6" aria-labelledby="preview-heading">
            <div class="flex items-baseline justify-between">
                <h2 id="preview-heading" class="text-base font-semibold">Preview</h2>
                <a href="{{ $group->publicUrl() }}" target="_blank" rel="noopener" class="text-sm font-medium text-slate-700 underline decoration-slate-300 hover:text-slate-900">Open public page<span class="sr-only"> (opens in a new tab)</span></a>
            </div>
            <p class="mt-1 text-xs text-slate-500">Unsaved changes are shown here only. Links in the preview do not open.</p>

            <div
                class="mt-3 max-h-[80vh] overflow-y-auto rounded-2xl border border-slate-200 bg-(--lg-background-color) px-4 py-10 text-(--lg-text-color) shadow-sm"
                style="{{ $group->appearanceStyle() }}"
                x-bind:style="style"
                x-on:click="if ($event.target.closest('a')) $event.preventDefault()"
                data-preview
            >
                <div class="mx-auto max-w-md">
                    <x-link-page :group="$group" :links="$links" :avatar-url="$avatarUrl" preview />
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
