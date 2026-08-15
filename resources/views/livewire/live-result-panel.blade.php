<div wire:poll.10s="checkForUpdates">
    @include('live-result.partials.content', [
        'categories' => $categories,
        'categoryGroups' => $categoryGroups,
        'selectedCategory' => $selectedCategory,
        'selectedRound' => $selectedRound,
        'sheetData' => $sheetData,
    ])
</div>
