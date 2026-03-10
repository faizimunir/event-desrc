<?php

namespace App\Livewire\Rewards;

use App\Models\Reward;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RewardList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('reward.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rewards()
    {
        return Reward::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('icon', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.rewards.reward-list');
    }
}
