<div wire:poll.10s="checkForUpdates">
    @include('live-result.partials.content', [
        'event' => $event,
        'categories' => $categories,
        'selectedCategory' => $selectedCategory,
        'selectedRound' => $selectedRound,
        'sheetData' => $sheetData,
    ])
</div>
