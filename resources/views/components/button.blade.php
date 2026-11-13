<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex w-full justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2']) }}>
    {{ $slot }}
</button>
