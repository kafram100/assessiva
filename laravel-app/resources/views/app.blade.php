<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script>
            (function () {
                try {
                    var k = 'ai-theme';
                    var stored = localStorage.getItem(k);
                    var dark =
                        stored === 'dark' ||
                        (stored !== 'light' &&
                            window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (dark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <meta name="description" content="Novagrade.ai for future universities. AI powered assessment, predictive analytics, personalized learning, and intelligent academic insights.">
        <meta name="application-name" content="Novagrade.ai">

        <title inertia>{{ config('app.name', 'Novagrade.ai') }}</title>

        <!-- Fonts: premium landing + app UI -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link
            href="https://fonts.bunny.net/css?family=inter:400,500,600,700|space-grotesk:500,600,700&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="app-bg h-full font-sans antialiased">
        @inertia
    </body>
</html>
