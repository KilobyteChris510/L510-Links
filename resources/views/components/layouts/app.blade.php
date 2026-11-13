@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-slate-900 antialiased">
        <div class="min-h-full">
            <header class="border-b border-slate-200 bg-white" x-data="{ open: false }">
                <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <a href="{{ route('dashboard') }}" class="text-base font-semibold tracking-tight">
                        Link Management
                    </a>

                    <div class="hidden items-center gap-4 sm:flex">
                        <span class="text-sm text-slate-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
                                Log out
                            </button>
                        </form>
                    </div>

                    <button
                        type="button"
                        class="rounded-md p-2 text-slate-600 hover:bg-slate-100 sm:hidden"
                        x-on:click="open = ! open"
                        x-bind:aria-expanded="open"
                        aria-controls="mobile-menu"
                    >
                        <span class="sr-only">Toggle menu</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div id="mobile-menu" class="border-t border-slate-200 px-4 py-3 sm:hidden" x-show="open" x-cloak>
                    <p class="text-sm text-slate-600">{{ auth()->user()->name }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-700 hover:text-slate-900">Log out</button>
                    </form>
                </div>
            </header>

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                <x-status-message />

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
