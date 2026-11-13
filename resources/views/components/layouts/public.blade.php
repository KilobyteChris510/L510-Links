@props(['title' => null])

{{-- Layout for future public link pages. Intentionally free of app navigation and auth state. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body {{ $attributes->merge(['class' => 'min-h-full bg-white font-sans text-slate-900 antialiased']) }}>
        <main class="mx-auto flex min-h-screen w-full max-w-md flex-col px-4 py-10">
            {{ $slot }}
        </main>
    </body>
</html>
