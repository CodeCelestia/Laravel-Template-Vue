<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference, theme, and sidebar color, and apply them immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                try {
                    const storedTheme = localStorage.getItem('theme_color') || 'ocean';
                    document.documentElement.setAttribute('data-theme', storedTheme);
                    let storedSidebar = localStorage.getItem('sidebar_color') || 'mist-blue';
                    if (storedSidebar === 'soft-blue' || storedSidebar === 'slate' || storedSidebar === 'navy') {
                        storedSidebar = 'mist-blue';
                    }
                    document.documentElement.setAttribute('data-sidebar', storedSidebar);
                } catch (e) {}
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

        @php
            $branding = \App\Models\Setting::getBranding();
        @endphp

        <link rel="icon" href="{{ $branding['favicon'] }}" sizes="any" id="app-favicon">
        <link rel="apple-touch-icon" href="{{ $branding['favicon'] }}" id="app-apple-touch-icon">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $branding['name'] }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
