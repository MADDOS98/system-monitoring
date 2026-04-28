<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'system-monitoring') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

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

        {{-- ── Hero ───────────────────────────────────────────── --}}
        <main class="relative z-10 flex-1 flex flex-col items-center justify-center text-center px-6 pt-24 pb-16">

            {{-- Logo icon --}}
            <div class="w-[52px] h-[52px] bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-7">
                <svg viewBox="0 0 16 16" fill="none" class="w-7 h-7">
                    <rect x="2" y="2" width="5" height="5" rx="1" fill="white"/>
                    <rect x="9" y="2" width="5" height="5" rx="1" fill="white" opacity=".6"/>
                    <rect x="2" y="9" width="5" height="5" rx="1" fill="white" opacity=".6"/>
                    <rect x="9" y="9" width="5" height="5" rx="1" fill="white" opacity=".3"/>
                </svg>
            </div>

            {{-- Headline --}}
            <h1 class="text-4xl sm:text-5xl font-medium text-[#f9fafb] leading-tight tracking-tight max-w-2xl mb-5">
                {{ config('app.name', 'server·monitoring') }}
            </h1>

            {{-- Subtitle --}}
            <p class="text-base text-[#6b7280] leading-relaxed max-w-md mb-10">
                {{ config('app.name', 'server·monitoring') }} gives your team a single pane
                of glass for servers, processes, logs and alerts — updated live, zero lag.
            </p>

            {{-- CTA button --}}
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="inline-flex items-center gap-2 text-sm font-medium text-white
                              bg-blue-600 hover:bg-blue-700 rounded-lg px-6 py-3
                              transition-colors duration-150">
                        Open Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 text-sm font-medium text-white
                              bg-blue-600 hover:bg-blue-700 rounded-lg px-6 py-3
                              transition-colors duration-150">
                        Sign in to workspace
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endauth
            @endif

        </main>
    </body>
</html>