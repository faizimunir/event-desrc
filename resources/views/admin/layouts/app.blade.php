<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 antialiased transition-colors duration-200">
    <!-- Page Loader -->
    <x-page-loader />
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 dark:bg-gray-900 text-white min-h-screen transition-colors duration-200">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-bold">Admin Panel</h1>
                    <x-dark-mode-toggle />
                </div>
                <p class="text-sm text-gray-400 mt-1">{{ auth('admin')->user()->name }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', auth('admin')->user()->role)) }}</p>
            </div>
            <nav class="mt-6">
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
                    Dashboard
                </a>
                
                @if(auth('admin')->user()->isSuperAdmin())
                    <a href="{{ route('admin.events.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.events.*') ? 'bg-gray-700' : '' }}">
                        Kelola Event
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.categories.*') ? 'bg-gray-700' : '' }}">
                        Kelola Kategori
                    </a>
                    <a href="{{ route('admin.packages.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.packages.*') ? 'bg-gray-700' : '' }}">
                        Kelola Paket
                    </a>
                    <a href="{{ route('admin.form-builder.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.form-builder.*') ? 'bg-gray-700' : '' }}">
                        Form Builder
                    </a>
                    <a href="{{ route('admin.payment-settings.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.payment-settings.*') ? 'bg-gray-700' : '' }}">
                        Pengaturan Pembayaran
                    </a>
                    <a href="{{ route('admin.registrations.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.registrations.*') ? 'bg-gray-700' : '' }}">
                        Data Registrasi
                    </a>
                    <a href="{{ route('admin.payment-proofs.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.payment-proofs.*') ? 'bg-gray-700' : '' }}">
                        Kelola Bukti Transfer
                    </a>
                    <a href="{{ route('admin.notifications.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.notifications.*') ? 'bg-gray-700' : '' }}">
                        Notifikasi
                    </a>
                    <a href="{{ route('admin.system-management.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.system-management.*') ? 'bg-gray-700' : '' }}">
                        System Management
                    </a>
                    <a href="{{ route('admin.print-center') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.print-center*') ? 'bg-gray-700' : '' }}">
                        🖨️ Cetak Hasil
                    </a>
                @elseif(auth('admin')->user()->isAdminEvent())
                    <a href="{{ route('admin.events.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.events.*') ? 'bg-gray-700' : '' }}">
                        Event Saya
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.categories.*') ? 'bg-gray-700' : '' }}">
                        Kelola Kategori
                    </a>
                    <a href="{{ route('admin.packages.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.packages.*') ? 'bg-gray-700' : '' }}">
                        Kelola Paket
                    </a>
                    <a href="{{ route('admin.form-builder.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.form-builder.*') ? 'bg-gray-700' : '' }}">
                        Form Builder
                    </a>
                    <a href="{{ route('admin.payment-settings.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.payment-settings.*') ? 'bg-gray-700' : '' }}">
                        Pengaturan Pembayaran
                    </a>
                    <a href="{{ route('admin.payment-proofs.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.payment-proofs.*') ? 'bg-gray-700' : '' }}">
                        Kelola Bukti Transfer
                    </a>
                    <a href="{{ route('admin.registrations.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.registrations.*') ? 'bg-gray-700' : '' }}">
                        Daftar Registrasi
                    </a>
                    <a href="{{ route('admin.system-management.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.system-management.*') ? 'bg-gray-700' : '' }}">
                        Kelola Admin
                    </a>
                @elseif(auth('admin')->user()->isCoAdminEvent())
                    <a href="{{ route('admin.events.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.events.*') ? 'bg-gray-700' : '' }}">
                        Event Saya
                    </a>
                    <a href="{{ route('admin.payment-proofs.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.payment-proofs.*') ? 'bg-gray-700' : '' }}">
                        Kelola Bukti Transfer
                    </a>
                    <a href="{{ route('admin.registrations.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.registrations.*') ? 'bg-gray-700' : '' }}">
                        Daftar Registrasi
                    </a>
                @endif
            </nav>
            <div class="mt-auto p-4">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-700 rounded">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>

