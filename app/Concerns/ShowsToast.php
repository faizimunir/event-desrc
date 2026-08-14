<?php

namespace App\Concerns;

trait ShowsToast
{
    protected function toast(string $message, string $variant = 'success', int $duration = 5000): void
    {
        $this->dispatch('toast-show',
            duration: $duration,
            slots: ['text' => $message],
            dataset: ['variant' => $variant],
        );
    }
}
