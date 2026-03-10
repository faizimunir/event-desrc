<footer class="[grid-area:footer] border-t border-zinc-200 dark:border-zinc-700/80 bg-zinc-50 dark:bg-zinc-900/50" data-flux-footer>
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Main footer content --}}
        <div class="py-12 sm:py-16 lg:py-20">
            <div class="mx-auto grid max-w-4xl grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                {{-- Brand column --}}
                <div>
                    <a href="{{ route('home') }}" class="inline-block focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded-lg">
                        <img src="{{ asset('logo-light.webp') }}" alt="{{ config('app.name') }}" class="h-8 dark:hidden" />
                        <img src="{{ asset('logo-dark.webp') }}" alt="{{ config('app.name') }}" class="hidden h-8 dark:block" />
                    </a>
                    <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400 max-w-xs">
                        {{ __('Platform pendaftaran event balap. Daftar lomba, pilih bracket, ikuti event favorit Anda.') }}
                    </p>
                    <a href="https://instagram.com/desracingcommittee" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 text-zinc-500 transition hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded" aria-label="Instagram">
                        <flux:icon name="instagram" variant="mini" class="size-5" />
                        <span class="text-sm">Instagram</span>
                    </a>
                </div>

                {{-- Platform links --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                        {{ __('Platform') }}
                    </h3>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <a href="{{ route('home') }}#events" class="text-sm text-zinc-600 dark:text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded">
                                {{ __('Events') }}
                            </a>
                        </li>
                        @auth
                            <li>
                                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-600 dark:text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded">
                                    {{ __('Dashboard') }}
                                </a>
                            </li>
                        @else
                            @if(Route::has('login'))
                                <li>
                                    <a href="{{ route('login') }}" class="text-sm text-zinc-600 dark:text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded">
                                        {{ __('Masuk') }}
                                    </a>
                                </li>
                            @endif
                            @if(Route::has('register'))
                                <li>
                                    <a href="{{ route('register') }}" class="text-sm text-zinc-600 dark:text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded">
                                        {{ __('Daftar') }}
                                    </a>
                                </li>
                            @endif
                        @endauth
                    </ul>
                </div>

                {{-- Legal / Info --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                        {{ __('Informasi') }}
                    </h3>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <a href="{{ route('home') }}" class="text-sm text-zinc-600 dark:text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded">
                                {{ __('Beranda') }}
                            </a>
                        </li>
                        <li>
                            <span class="text-sm text-zinc-500 dark:text-zinc-500">{{ __('Kebijakan privasi') }}</span>
                        </li>
                        <li>
                            <span class="text-sm text-zinc-500 dark:text-zinc-500">{{ __('Syarat & ketentuan') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-zinc-200 dark:border-zinc-700/80 py-6">
            <div class="mx-auto flex max-w-4xl flex-col items-center justify-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </div>
    </div>
</footer>
