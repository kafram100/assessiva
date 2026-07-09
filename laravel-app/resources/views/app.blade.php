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
                    // Default is LIGHT. Only enable dark if user explicitly chose it.
                    if (stored === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <meta name="description" content="NovaGrade: The ultimate AI-powered practice hub. Test your knowledge with randomized exams and get detailed feedback to master your subjects.">
        <meta name="application-name" content="NovaGrade">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'NovaGrade') }}</title>
        <link rel="icon" type="image/png" href="/favicon.png" />

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
