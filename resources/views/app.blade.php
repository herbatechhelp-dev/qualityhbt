<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Dynamic Favicon -->
        @php
            $faviconPath = \App\Models\Setting::getValue('app_favicon_path');
        @endphp
        @if($faviconPath)
            <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $faviconPath) }}">
            <link rel="shortcut icon" href="{{ asset('storage/' . $faviconPath) }}">
        @else
            <link rel="icon" type="image/x-icon" href="/favicon.ico">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
