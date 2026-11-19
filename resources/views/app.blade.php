<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- The site is light only. --}}
        <style>
            html {
                background-color: #edf7fa;
                color-scheme: light;
            }
        </style>

        @php($site = app(\App\Services\AppSettings::class)->site())
        @if ($site['favicon_url'])
            <link rel="icon" href="{{ $site['favicon_url'] }}">
            <link rel="apple-touch-icon" href="{{ $site['favicon_url'] }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif
        @if ($site['description'] !== '')
            <meta name="description" content="{{ $site['description'] }}">
        @endif

        {{-- Quote pages share their own $og; every other page falls back to the site card. --}}
        @php($og = $og ?? app(\App\Services\OgMeta::class)->default())
        @if ($og['canonical'])
            <link rel="canonical" href="{{ $og['url'] }}">
        @endif
        <meta property="og:type" content="{{ $og['type'] }}">
        <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
        <meta property="og:site_name" content="{{ $og['site_name'] }}">
        <meta property="og:title" content="{{ $og['title'] }}">
        <meta property="og:description" content="{{ $og['description'] }}">
        <meta property="og:url" content="{{ $og['url'] }}">
        <meta property="og:image" content="{{ $og['image'] }}">
        <meta property="og:image:secure_url" content="{{ $og['image'] }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $og['image_alt'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $og['title'] }}">
        <meta name="twitter:description" content="{{ $og['description'] }}">
        <meta name="twitter:image" content="{{ $og['image'] }}">
        <meta name="twitter:image:alt" content="{{ $og['image_alt'] }}">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $site['name'] }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
