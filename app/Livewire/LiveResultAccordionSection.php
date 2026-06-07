<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LiveResultAccordionSection extends Component
{
    public int $limit = 6;

    public ?string $sectionId = null;

    public bool $animate = false;

    public function mount(int $limit = 6, ?string $sectionId = null, bool $animate = false): void
    {
        $this->limit = $limit;
        $this->sectionId = $sectionId;
        $this->animate = $animate;
    }

    public function render(): View
    {
        $events = Event::with('location')
            ->visibleOnHomePage()
            ->where('has_live_result', true)
            ->orderBy('start_at', 'desc')
            ->limit($this->limit)
            ->get();

        return view('livewire.live-result-accordion-section', [
            'events' => $events,
        ]);
    }
}
