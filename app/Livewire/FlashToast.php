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
                slots: ['text' => $this->statusMessage(session('status'))],
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

    private function statusMessage(mixed $status): string
    {
        return match ($status) {
            'verification-link-sent' => __('A new verification link has been sent to your email address.'),
            default => (string) $status,
        };
    }

    public function render()
    {
        return view('livewire.flash-toast');
    }
}
