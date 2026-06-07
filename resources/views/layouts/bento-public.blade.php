<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => trim($__env->yieldContent('title')) ?: null])
    @stack('head')
</head>
<body class="bento-page">
    <div class="bento-shell">
        @include('partials.navbar-bento')

        <main class="mt-4 space-y-4 sm:space-y-5">
            @yield('content')

            <div class="bento-card bento-footer">
                @include('partials.footer-bento')
            </div>
        </main>
    </div>

    @fluxScripts
    @stack('scripts')
</body>
</html>
