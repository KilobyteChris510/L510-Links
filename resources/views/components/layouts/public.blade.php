@props(['title' => null, 'description' => null, 'canonical' => null, 'themeColor' => null])

{{-- Layout for public link pages. Free of app navigation, auth state, and JavaScript. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
            <meta property="og:url" content="{{ $canonical }}">
        @endif
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title ?? config('app.name') }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        @if ($themeColor)
            <meta name="theme-color" content="{{ $themeColor }}">
        @endif

        @fonts
        @vite('resources/css/app.css')
    </head>
    <body {{ $attributes->merge(['class' => 'min-h-full font-sans antialiased']) }}>
        <main class="mx-auto flex min-h-screen w-full max-w-md flex-col px-4 py-10 sm:py-16">
            {{ $slot }}
        </main>
    </body>
</html>
