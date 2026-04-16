<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EventCardsSection extends Component
{
    public ?int $limit = null;

    public ?string $sectionId = null;

    public bool $animate = false;

    public bool $showHeader = true;

    public function mount(?int $limit = null, ?string $sectionId = null, bool $animate = false, bool $showHeader = true): void
    {
        $this->limit = $limit;
        $this->sectionId = $sectionId;
        $this->animate = $animate;
        $this->showHeader = $showHeader;
    }

    public function render(): View
    {
        $query = Event::with('location')
            ->visibleOnHomePage()
            ->orderBy('start_at', 'desc');

        if ($this->limit) {
            $query->limit($this->limit);
        }

        return view('livewire.event-cards-section', [
            'events' => $query->get(),
        ]);
    }
}
