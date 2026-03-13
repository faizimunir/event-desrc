<?php

namespace App\Livewire\Accounts;

use App\Models\Account;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AccountList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('account.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function accounts()
    {
        return Account::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('acc_name', 'like', '%'.$this->search.'%')
                        ->orWhere('acc_bank', 'like', '%'.$this->search.'%')
                        ->orWhere('acc_number', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.accounts.account-list');
    }
}
