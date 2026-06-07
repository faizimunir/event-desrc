<footer class="[grid-area:footer]" data-flux-footer>
    <div class="mx-auto w-full">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
            <div>
                <a href="{{ route('home') }}" class="inline-block rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2">
                    <img src="{{ asset('logo-light.webp') }}" alt="{{ config('app.name') }}" class="h-8 dark:hidden" />
                    <img src="{{ asset('logo-dark.webp') }}" alt="{{ config('app.name') }}" class="hidden h-8 dark:block" />
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ __('Platform pendaftaran event balap. Daftar lomba, pilih bracket, ikuti event favorit Anda.') }}
                </p>
                <a href="https://instagram.com/desracingcommittee" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-lg text-zinc-500 transition hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-zinc-200" aria-label="Instagram">
                    <flux:icon name="instagram" variant="mini" class="size-5" />
                    <span class="text-sm">Instagram</span>
                </a>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                    {{ __('Platform') }}
                </h3>
                <ul class="mt-4 space-y-3">
                    <li>
                        <a href="{{ route('home') }}#events" class="rounded-lg text-sm text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-white">
                            {{ __('Events') }}
                        </a>
                    </li>
                    <li>
                        <a href="https://app.desrc.id/" target="_blank" rel="noopener noreferrer" class="rounded-lg text-sm text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-white">
                            {{ __('Aplikasi') }}
                        </a>
                    </li>
                    @auth
                        <li>
                            <a href="{{ route('dashboard') }}" class="rounded-lg text-sm text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-white">
                                {{ __('Dashboard') }}
                            </a>
                        </li>
                    @else
                        @if(Route::has('login'))
                            <li>
                                <a href="{{ route('login') }}" class="rounded-lg text-sm text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-white">
                                    {{ __('Masuk') }}
                                </a>
                            </li>
                        @endif
                        @if(Route::has('register'))
                            <li>
                                <a href="{{ route('register') }}" class="rounded-lg text-sm text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-white">
                                    {{ __('Daftar') }}
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                    {{ __('Informasi') }}
                </h3>
                <ul class="mt-4 space-y-3">
                    <li>
                        <a href="{{ route('home') }}" class="rounded-lg text-sm text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-white">
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

        <div class="mt-10 border-t border-zinc-200/80 pt-6 dark:border-zinc-700/80">
            <p class="text-center text-sm text-zinc-500 dark:text-zinc-400 sm:text-left">
                © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</footer>
