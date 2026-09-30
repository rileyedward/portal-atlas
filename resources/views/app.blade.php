<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'dark') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "dark" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        {{-- Server-rendered meta for crawlers and link previews; SeoHead.vue takes over on client visits. --}}
        @php
            $seo = $page['props']['seo'] ?? \App\Support\Seo::make();
            $siteName = config('app.name');
            $fullTitle = $seo['title'] ? "{$seo['title']} - {$siteName}" : $siteName;
            $ogImage = file_exists(public_path('og-image.png')) ? asset('og-image.png') : null;
            $websiteSchema = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $siteName,
                'alternateName' => ['Active Matter Interactive Map', 'Active Matter Map'],
                'url' => route('home'),
                'description' => $seo['description'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        @endphp
        @if ($seo['noindex'])
            <meta name="robots" content="noindex">
        @endif
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
        @endif
        @if (request()->routeIs('home'))
            <script type="application/ld+json">{!! $websiteSchema !!}</script>
        @endif

        <x-inertia::head>
            <title>{{ $fullTitle }}</title>
            <link data-inertia="canonical" rel="canonical" href="{{ $seo['url'] }}">
            <meta data-inertia="description" name="description" content="{{ $seo['description'] }}">
            <meta data-inertia="og:title" property="og:title" content="{{ $fullTitle }}">
            <meta data-inertia="og:description" property="og:description" content="{{ $seo['description'] }}">
            <meta data-inertia="og:url" property="og:url" content="{{ $seo['url'] }}">
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
