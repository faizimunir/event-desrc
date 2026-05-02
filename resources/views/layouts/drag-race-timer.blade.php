<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', __('Drag Race Timer')) — {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600;family=jetbrains-mono:500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/drag-race-timer.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased font-sans selection:bg-emerald-500/30">
    @yield('content')
</body>
</html>
