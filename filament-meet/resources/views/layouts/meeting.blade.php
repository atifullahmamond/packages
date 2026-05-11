<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full"
    x-data="{ darkMode: localStorage.getItem('theme') === 'dark' || window.matchMedia('(prefers-color-scheme: dark)').matches }"
    :class="{ 'dark': darkMode }"
>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Meeting Room' }} — {{ config('app.name') }}</title>

    @filamentStyles
    @livewireStyles
    {{-- Avoid hard-failing when Vite is not running in dev (no public/hot) and no prod build yet. --}}
    @php
        $filamentMeetViteReady = file_exists(public_path('hot'))
            || file_exists(public_path('build/manifest.json'))
            || file_exists(public_path('build/.vite/manifest.json'));
    @endphp
    @if ($filamentMeetViteReady)
        @vite(['resources/css/app.css'])
    @endif

    <style>
        [x-cloak] { display: none !important; }

        html, body { height: 100%; overflow: hidden; }

        .meeting-layout {
            height: 100dvh;
            display: flex;
            flex-direction: column;
            background-color: #0f0f11;
            color: #f0f0f5;
        }

        .dark .meeting-layout {
            background-color: #0a0a0c;
        }

        /*
         | Host apps often only Tailwind-scan resources/**/*.blade.php, so utilities in this
         | package are missing from Vite builds. These rules keep the shell + Jitsi mount usable.
         */
        .meeting-layout > .meet-app {
            flex: 1 1 0%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .meeting-layout .meet-main {
            flex: 1 1 0%;
            min-height: 0;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            position: relative;
        }

        .meeting-layout .meet-stage {
            flex: 1 1 0%;
            min-width: 0;
            min-height: 0;
            position: relative;
            overflow: hidden;
            background-color: #09090b;
        }

        .meeting-layout #jitsi-container {
            width: 100%;
            height: 100%;
            min-height: 240px;
        }

        .meeting-layout .meet-app > header,
        .meeting-layout .meet-app > footer {
            flex-shrink: 0;
        }

        /* Custom scrollbar for participant list */
        .participant-scroll::-webkit-scrollbar { width: 4px; }
        .participant-scroll::-webkit-scrollbar-track { background: transparent; }
        .participant-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }
    </style>
</head>
<body class="h-full antialiased font-sans text-zinc-100">
    <div class="meeting-layout">
        @yield('content')
    </div>

    {{-- Meeting room defines global Alpine helpers; must run before Filament/Livewire boot Alpine. --}}
    @stack('scripts')

    @filamentScripts
    @livewireScripts

    <script>
        {{-- livewire:init / livewire:initialized already fired while the Livewire bundle ran; register listeners synchronously. --}}
        (function () {
            if (typeof Livewire === 'undefined' || typeof Livewire.on !== 'function') {
                return;
            }
            Livewire.on('redirect-to-panel', ({ url }) => {
                if (url) {
                    window.location.href = url;
                }
            });
        })();
    </script>
</body>
</html>
