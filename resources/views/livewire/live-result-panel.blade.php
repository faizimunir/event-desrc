<div wire:poll.10s="checkForUpdates" class="live-result-panel relative min-h-[12rem]">
    <div
        wire:loading.delay.shortest.flex
        wire:target="selectCategory,selectRound"
        class="absolute inset-0 z-20 items-center justify-center rounded-2xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-950/80"
        aria-live="polite"
        aria-busy="true"
    >
        <div class="live-result-loading-spinner" aria-hidden="true"></div>
    </div>

    @include('live-result.partials.content', [
        'event' => $event,
        'categories' => $categories,
        'selectedCategory' => $selectedCategory,
        'selectedRound' => $selectedRound,
        'sheetData' => $sheetData,
    ])
</div>
