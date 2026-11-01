<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistem Anomali Sensus') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-ios-bg">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-display font-semibold text-ios-label tracking-tight">Sistem Anomali Sensus</h1>
                <p class="text-sm text-ios-secondary mt-1">BPS Kota Kediri</p>
            </div>

            <div class="w-full sm:max-w-md bg-ios-card rounded-ios shadow-sm border border-ios-border px-8 py-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>