<?php

namespace App\Livewire;

use Livewire\Component;

class FlashToast extends Component
{
    public function mount(): void
    {
        if (session()->has('status')) {
            $this->dispatch('toast-show',
                duration: 5000,
                slots: ['text' => session('status')],
                dataset: ['variant' => 'success'],
            );
        }

        if (session()->has('error')) {
            $this->dispatch('toast-show',
                duration: 5000,
                slots: ['text' => session('error')],
                dataset: ['variant' => 'danger'],
            );
        }
    }

    public function render()
    {
        return view('livewire.flash-toast');
    }
}
