<x-layouts.public
    :title="$group->name"
    :description="$group->description ? Str::limit(Str::squish($group->description), 160) : null"
    :canonical="$group->publicUrl()"
    :theme-color="$group->appearanceSettings()['background_color']"
    style="{{ $group->appearanceStyle() }}"
    class="bg-(--lg-background-color) text-(--lg-text-color)"
>
    <x-link-page :group="$group" :links="$group->links" :avatar-url="$group->avatarUrl()" />
</x-layouts.public>
