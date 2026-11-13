<x-layouts.app :title="'Share · '.$group->name">
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
        <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>
        <span class="flex gap-4">
            <a href="{{ route('groups.links.index', $group) }}" class="font-medium text-slate-700 hover:text-slate-900">Manage links</a>
            <a href="{{ route('groups.edit', $group) }}" class="font-medium text-slate-700 hover:text-slate-900">Group settings</a>
        </span>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight break-words">Share: {{ $group->name }}</h1>
        <x-status-badge :active="$group->is_active" />
    </div>

    @unless ($group->is_active)
        <div class="mt-4 flex gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            <span aria-hidden="true">&#9888;</span>
            <p>
                <span class="font-semibold">This group is inactive.</span>
                Its public URL and QR code currently show a "not found" page. They will work again, unchanged, as soon as you
                <a href="{{ route('groups.edit', $group) }}" class="font-semibold underline">reactivate the group</a>.
            </p>
        </div>
    @endunless

    <div class="mt-6 grid gap-6 lg:grid-cols-2 lg:items-start">
        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="url-heading">
                <h2 id="url-heading" class="text-base font-semibold">Permanent public URL</h2>
                <p class="mt-1 text-sm text-slate-600">This URL never changes, even when you rename the group, edit its links, or change its appearance.</p>
                <x-copy-url class="mt-3" :url="$group->publicUrl()" />
                <a href="{{ $group->publicUrl() }}" target="_blank" rel="noopener" class="mt-4 inline-flex w-full justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 sm:w-auto">
                    Open public page<span class="sr-only"> (opens in a new tab)</span>
                </a>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="nfc-heading">
                <h2 id="nfc-heading" class="text-base font-semibold">Using the URL with an NFC card</h2>
                <p class="mt-1 text-sm text-slate-600">This app doesn't program NFC cards. Use any NFC writing app on your phone or computer:</p>
                <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-slate-700">
                    <li>Copy the public URL above.</li>
                    <li>Open an NFC writing application.</li>
                    <li>Create a URL/URI record containing the copied URL.</li>
                    <li>Write the record to the NFC card.</li>
                    <li>Tap the card with a smartphone to check that it opens this group's page.</li>
                </ol>
                <p class="mt-3 text-sm text-slate-600"><span class="font-semibold text-slate-800">Before locking a card</span> against further writes, confirm it opens the right page. A locked card can't be corrected, but you can keep changing the links it shows here.</p>
            </section>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="qr-heading">
            <h2 id="qr-heading" class="text-base font-semibold">QR code</h2>
            <p class="mt-1 text-sm text-slate-600">Scanning this code opens <span class="font-medium break-words text-slate-800">{{ $group->publicUrl() }}</span>.</p>

            <img
                src="{{ $qrDataUri }}"
                alt="QR code that opens {{ $group->publicUrl() }}"
                width="288"
                height="288"
                class="mx-auto mt-4 h-64 w-64 rounded-md border border-slate-200 bg-white sm:h-72 sm:w-72"
            >

            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('groups.qr.svg', $group) }}" download class="inline-flex flex-1 justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">Download SVG</a>
                <a href="{{ route('groups.qr.png', $group) }}" download class="inline-flex flex-1 justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">Download PNG</a>
            </div>
            <p class="mt-3 text-xs text-slate-500">SVG is best for print because it scales without losing sharpness. PNG is a high-resolution image for documents and screens. Print the code at least 2 cm (0.8 in) wide and keep the white border around it.</p>
        </section>
    </div>
</x-layouts.app>
