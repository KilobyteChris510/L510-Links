@props(['url', 'label' => 'Public URL'])

<div
    x-data="{
        copied: false,
        copy() {
            const done = () => { this.copied = true; setTimeout(() => this.copied = false, 2000); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(this.$refs.url.value).then(done);
            } else {
                this.$refs.url.select();
                document.execCommand('copy');
                done();
            }
        },
    }"
    {{ $attributes->merge(['class' => 'flex gap-2']) }}
>
    <input
        x-ref="url"
        type="text"
        readonly
        value="{{ $url }}"
        aria-label="{{ $label }}"
        x-on:focus="$event.target.select()"
        class="block min-w-0 flex-1 rounded-md border border-slate-300 bg-slate-50 px-3 py-2 font-mono text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
    >
    <button
        type="button"
        x-on:click="copy()"
        class="shrink-0 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
    >
        <span x-show="! copied">Copy link</span>
        <span x-show="copied" x-cloak>Copied</span>
    </button>
    <span class="sr-only" aria-live="polite" x-text="copied ? 'Link copied to clipboard' : ''"></span>
</div>
