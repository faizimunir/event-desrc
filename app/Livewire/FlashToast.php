<?php

namespace App\Livewire;

use App\Concerns\ShowsToast;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component;

class FlashToast extends Component
{
    use ShowsToast;

    public function mount(): void
    {
        if (session()->has('status')) {
            $this->toast($this->statusMessage(session('status')));
        }

        if (session()->has('error')) {
            $this->toast((string) session('error'), 'danger');
        } elseif ($message = $this->firstValidationError()) {
            $this->toast($message, 'danger');
        }

        if (session()->has('checkin_success')) {
            $this->toast($this->checkinSuccessMessage(session('checkin_success')));
        }
    }

    public function render()
    {
        return view('livewire.flash-toast');
    }

    private function statusMessage(mixed $status): string
    {
        return match ($status) {
            'verification-link-sent' => __('A new verification link has been sent to your email address.'),
            default => (string) $status,
        };
    }

    private function firstValidationError(): ?string
    {
        $errors = session('errors');

        if (! $errors instanceof ViewErrorBag || ! $errors->any()) {
            return null;
        }

        $message = $errors->first();

        return is_string($message) && $message !== '' ? $message : null;
    }

    private function checkinSuccessMessage(mixed $summary): string
    {
        if (! is_array($summary)) {
            return __('Check-in recorded.');
        }

        $parts = array_filter([
            $summary['name'] ?? null,
            filled($summary['number_plate'] ?? null) ? '#'.$summary['number_plate'] : null,
            $summary['teams'] ?? null,
            $summary['bracket'] ?? null,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : __('Check-in recorded.');
    }
}
