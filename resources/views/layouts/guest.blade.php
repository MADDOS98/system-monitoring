<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'system-monitoring') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .dot-grid::before {
                content: '';
                position: fixed;
                inset: 0;
                background-image: radial-gradient(circle, #2a2a2a 1px, transparent 1px);
                background-size: 28px 28px;
                pointer-events: none;
                opacity: 0.5;
                z-index: 0;
            }
        </style>
    </head>
    <body class="dot-grid bg-[#111111] text-[#e5e7eb] min-h-screen flex flex-col font-sans antialiased">

        <div class="relative z-10 min-h-screen flex flex-col sm:justify-center items-center px-6">

            <!-- Logo -->
            <div class="mb-6">
                <a href="/">
                    <x-application-logo class="w-16 h-16 fill-current text-gray-400" />
                </a>
            </div>

            <!-- Card -->
            <div class="w-full sm:max-w-md px-6 py-6
                        bg-[#1a1a1a]
                        border border-[#2a2a2a]
                        rounded-xl
                        shadow-lg">

                {{ $slot }}

            </div>

        </div>
    </body>
</html>