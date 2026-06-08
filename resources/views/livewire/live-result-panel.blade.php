<div wire:poll.10s="checkForUpdates">
    @include('live-result.partials.content', [
        'categories' => $categories,
        'selectedCategory' => $selectedCategory,
        'selectedRound' => $selectedRound,
        'sheetData' => $sheetData,
    ])
</div>
