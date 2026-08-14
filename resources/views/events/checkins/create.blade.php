<x-layouts::app :title="__('Record check-in')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'checkin'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Record check-in') }}</flux:heading>

        @if (session('checkin_success'))
            <x-checkin-success-callout :summary="session('checkin_success')" />
        @endif

        <livewire:events.event-checkin-form :event="$event" />
    </div>
</x-layouts::app>
