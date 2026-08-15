<div
    wire:poll.5s="tick"
    x-data="{
        nowMs: Date.parse(@js($clockIso)),
        tickClock() { this.nowMs = Date.now() },
        clock() {
            const d = new Date(this.nowMs)
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })
        }
    }"
    x-init="setInterval(() => tickClock(), 1000)"
>
    @include('live-result.partials.content', [
        'event' => $event,
        'categories' => $categories,
        'categoryGroups' => $categoryGroups,
        'selectedCategory' => $selectedCategory,
        'selectedRound' => $selectedRound,
        'sheetData' => $sheetData,
        'now' => $now,
        'monitorSummary' => $monitorSummary,
    ])
</div>
