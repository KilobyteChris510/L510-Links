@php
    $avatarUrl = $group->avatarUrl();
    $metaDescription = $group->description ? Str::limit(Str::squish($group->description), 160) : null;
@endphp

<x-layouts.public :title="$group->name" :description="$metaDescription" :canonical="$group->publicUrl()">
    <header class="text-center">
        @if ($avatarUrl)
            <img
                src="{{ $avatarUrl }}"
                alt="Profile image for {{ $group->name }}"
                width="96"
                height="96"
                class="mx-auto mb-5 h-24 w-24 rounded-full object-cover shadow-sm ring-4 ring-white"
            >
        @endif

        <h1 class="text-2xl font-bold tracking-tight break-words text-slate-900 sm:text-3xl">{{ $group->name }}</h1>

        @if ($group->description)
            <p class="mt-3 text-base leading-relaxed break-words whitespace-pre-line text-slate-700">{{ $group->description }}</p>
        @endif
    </header>

    @if ($group->links->isEmpty())
        <p class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white/60 px-6 py-8 text-center text-sm text-slate-600">
            There are no links here yet. Please check back soon.
        </p>
    @else
        <ul class="mt-8 space-y-3" role="list">
            @foreach ($group->links as $link)
                <li>
                    @if ($link->hasSafeUrl())
                        <a
                            href="{{ $link->url }}"
                            class="group flex min-h-14 items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 hover:shadow-md hover:ring-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 active:bg-slate-50 motion-safe:transition motion-safe:active:scale-[0.99]"
                        >
                            <span class="w-5 shrink-0" aria-hidden="true"></span>
                            <span class="min-w-0 flex-1 text-center">
                                <span class="block font-semibold break-words text-slate-900">{{ $link->title }}</span>
                                @if ($link->description)
                                    <span class="mt-0.5 block text-sm break-words text-slate-600">{{ $link->description }}</span>
                                @endif
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-slate-500 group-hover:text-slate-800 motion-safe:transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <div class="flex min-h-14 flex-col justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 px-5 py-4 text-center">
                            <span class="font-semibold break-words text-slate-700">{{ $link->title }}</span>
                            <span class="mt-0.5 text-sm text-slate-600">This link is currently unavailable.</span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.public>
