<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LiveResultAccordionSection extends Component
{
    public ?int $limit = null;

    public ?string $sectionId = null;

    public bool $animate = false;

    public ?bool $showViewAll = null;

    public function mount(?int $limit = null, ?string $sectionId = null, bool $animate = false, ?bool $showViewAll = null): void
    {
        $this->limit = $limit;
        $this->sectionId = $sectionId;
        $this->animate = $animate;
        $this->showViewAll = $showViewAll;
    }

    public function render(): View
    {
        $query = Event::with('location')
            ->visibleOnHomePage()
            ->where('has_live_result', true)
            ->orderBy('start_at', 'desc');

        if ($this->limit) {
            $query->limit($this->limit);
        }

        $events = $query->get();

        $showViewAll = $this->showViewAll ?? (
            $this->limit !== null && $events->count() >= $this->limit
        );

        return view('livewire.live-result-accordion-section', [
            'events' => $events,
            'showViewAll' => $showViewAll,
        ]);
    }
}
