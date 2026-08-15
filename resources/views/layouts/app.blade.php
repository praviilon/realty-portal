<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 mt-12">
                <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 text-sm text-gray-500">
                    <div>&copy; {{ now()->year }} {{ config('app.name', 'А-Недвижимость') }}. Все права защищены.</div>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <a href="#" class="hover:text-gray-800">О компании</a>
                        <a href="#" class="hover:text-gray-800">Правовая информация</a>
                        <a href="#" class="hover:text-gray-800">FAQ</a>
                        <a href="#" class="hover:text-gray-800">Контакты</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
