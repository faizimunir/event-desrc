<section
    @if($sectionId) id="{{ $sectionId }}" @endif
    class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16"
>
    <header class="mb-6 sm:mb-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                    {{ __('Live Result') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Pilih event untuk melihat hasil live.') }}
                </p>
            </div>
        </div>
    </header>

    @if($events->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 py-12 text-center dark:border-zinc-700 dark:bg-zinc-800/30">
            <flux:icon name="chart-bar" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada live result') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event yang tersedia.') }}</p>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/30 p-4 sm:p-6">
            <flux:accordion>
                @foreach($events as $ev)
                    <flux:accordion.item heading="{{ $ev->title }}" expanded>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                @if($ev->start_at)
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="calendar-days" class="size-4 text-zinc-400" />
                                        <span>{{ $ev->start_at->format('d M Y, H:i') }}</span>
                                    </div>
                                @endif
                                @if($ev->location)
                                    <div class="mt-1 flex items-center gap-2">
                                        <flux:icon name="map-pin" class="size-4 text-zinc-400" />
                                        <span>{{ $ev->location->name }}</span>
                                    </div>
                                @endif
                            </div>

                            <flux:button variant="primary" size="sm" href="{{ route('live-result.show', $ev->slug) }}" wire:navigate>
                                {{ __('Buka live result') }}
                            </flux:button>
                        </div>
                    </flux:accordion.item>
                @endforeach
            </flux:accordion>
        </div>
    @endif
</section>

