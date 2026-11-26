<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="MoM adalah sistem notulensi terintegrasi AI untuk mencatat, mendistribusikan, dan menindaklanjuti hasil rapat.">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#f7f2ea">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <script>
            (function () {
                try {
                    const serverAppearance = @json(auth()->user()?->appearance);
                    window.__MOM_SERVER_APPEARANCE__ = serverAppearance;
                    const saved = serverAppearance || localStorage.getItem('appearance') || 'system';
                    const dark = saved === 'dark' || (saved === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#12100e' : '#f7f2ea');
                } catch (error) {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
