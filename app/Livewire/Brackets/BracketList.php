<?php

namespace App\Livewire\Brackets;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BracketList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public bool $hideAllQuota = false;

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('bracket.read'), 403);
        $this->event = $event;
        $this->syncHideAllQuotaFromBrackets();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedHideAllQuota(): void
    {
        abort_unless(auth()->user()->canAs('bracket.update'), 403);
        $this->event->brackets()->update(['hide_quota' => $this->hideAllQuota]);
    }

    private function syncHideAllQuotaFromBrackets(): void
    {
        $hasBrackets = $this->event->brackets()->exists();
        $allHide = $hasBrackets && ! $this->event->brackets()->where('hide_quota', false)->exists();
        $this->hideAllQuota = $allHide;
    }

    #[Computed]
    public function brackets()
    {
        return $this->event->brackets()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.brackets.bracket-list');
    }
}
