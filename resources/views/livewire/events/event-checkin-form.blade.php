<div class="max-w-2xl space-y-6">
    <div class="flex flex-wrap items-end gap-3">
        @php
            $scannerRegionId = 'event-checkin-scanner-' . $event->id;
        @endphp
        <div wire:ignore>
            @include('partials.event-checkin-scanner', [
                'scannerRegionId' => $scannerRegionId,
            ])
        </div>
    </div>

    @if ($this->registrationsAvailableForCheckin->isNotEmpty())
        <form method="POST" action="{{ route('events.checkins.store', $event) }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select
                    name="registration_id"
                    variant="listbox"
                    searchable
                    :label="__('Registration')"
                    :placeholder="__('Select participant…')"
                    required
                    class="w-full"
                >
                    @foreach ($this->registrationsAvailableForCheckin as $reg)
                        <flux:select.option :value="$reg->id">
                            {{ $reg->rider?->name ?? __('Rider') }} — {{ $reg->number_plate ?? '—' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input name="notes" type="text" :label="__('Notes (optional)')" :placeholder="__('e.g. Gate A')" />
            </div>
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary" icon="check">{{ __('Check in') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'checkin'])" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    @else
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No pending registrations. Scan a ticket QR code to check in.') }}</p>
        <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'checkin'])" wire:navigate>{{ __('Cancel') }}</flux:button>
    @endif
</div>
