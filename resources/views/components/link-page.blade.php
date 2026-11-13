@props(['group', 'links', 'avatarUrl' => null, 'preview' => false])

{{--
    Public link page content, shared by the public page and the appearance editor preview.
    Colors and button shape come from the --lg-* CSS custom properties set by the parent.
--}}
<header class="text-center">
    @if ($preview)
        <img
            x-show="avatar"
            x-bind:src="avatar"
            @if ($avatarUrl) src="{{ $avatarUrl }}" @endif
            @unless ($avatarUrl) style="display: none" @endunless
            alt="Profile image for {{ $group->name }}"
            class="mx-auto mb-5 h-24 w-24 rounded-full object-cover shadow-sm ring-4 ring-(--lg-button-background-color)"
        >
    @elseif ($avatarUrl)
        <img
            src="{{ $avatarUrl }}"
            alt="Profile image for {{ $group->name }}"
            width="96"
            height="96"
            class="mx-auto mb-5 h-24 w-24 rounded-full object-cover shadow-sm ring-4 ring-(--lg-button-background-color)"
        >
    @endif

    <{{ $preview ? 'h2' : 'h1' }} class="text-2xl font-bold tracking-tight break-words text-(--lg-text-color) sm:text-3xl">{{ $group->name }}</{{ $preview ? 'h2' : 'h1' }}>

    @if ($group->description)
        <p class="mt-3 text-base leading-relaxed break-words whitespace-pre-line text-(--lg-text-color)/85">{{ $group->description }}</p>
    @endif
</header>

@if ($links->isEmpty())
    <p class="mt-10 rounded-(--lg-button-radius) border border-dashed border-(--lg-text-color)/30 px-6 py-8 text-center text-sm text-(--lg-text-color)/85">
        There are no links here yet. Please check back soon.
    </p>
@else
    <ul class="mt-8 space-y-3" role="list">
        @foreach ($links as $link)
            <li>
                @if ($link->hasSafeUrl())
                    <a
                        href="{{ $link->url }}"
                        class="group flex min-h-14 items-center gap-3 rounded-(--lg-button-radius) bg-(--lg-button-background-color) px-5 py-4 text-(--lg-button-text-color) shadow-sm ring-1 ring-(--lg-text-color)/15 hover:shadow-md hover:ring-(--lg-text-color)/40 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-(--lg-text-color) active:opacity-90 motion-safe:transition"
                    >
                        <span class="w-5 shrink-0" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1 text-center">
                            <span class="block font-semibold break-words">{{ $link->title }}</span>
                            @if ($link->description)
                                <span class="mt-0.5 block text-sm break-words text-(--lg-button-text-color)/80">{{ $link->description }}</span>
                            @endif
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-(--lg-button-text-color)/60 group-hover:text-(--lg-button-text-color) motion-safe:transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <div class="flex min-h-14 flex-col justify-center rounded-(--lg-button-radius) border border-dashed border-(--lg-text-color)/30 px-5 py-4 text-center text-(--lg-text-color)/85">
                        <span class="font-semibold break-words">{{ $link->title }}</span>
                        <span class="mt-0.5 text-sm">This link is currently unavailable.</span>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@endif
