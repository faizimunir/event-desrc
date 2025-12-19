<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Event Desrc') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 antialiased transition-colors duration-200">
    <!-- Page Loader -->
    <x-page-loader />
    
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200" wire:navigate>
                        Event Desrc
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('home') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200" wire:navigate>
                        Home
                    </a>
                    <a href="{{ route('result.index') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200" wire:navigate>
                        Live Result
                    </a>
                    <x-dark-mode-toggle />
                    @auth('admin')
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Admin
                        </a>
                    @else
                        <a href="{{ route('admin.login') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Login Admin
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12 transition-colors duration-200">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                &copy; {{ date('Y') }} {{ config('app.name', 'Event Desrc') }}. All rights reserved.
            </p>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>

