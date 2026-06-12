<div>
    @if (session('status'))
        <flux:callout variant="success" class="rounded-lg mb-4">{{ session('status') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger" class="rounded-lg mb-4">{{ session('error') }}</flux:callout>
    @endif

    @canAs('checkin.create')
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-6 mb-6">
            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">{{ __('Record check-in') }}</h2>

            @if ($scanMessage)
                @php
                    $scanAlertVariant = match ($scanMessageType) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    };
                @endphp
                <flux:callout :variant="$scanAlertVariant" class="rounded-lg mb-4">{{ $scanMessage }}</flux:callout>
            @endif

            <div class="flex flex-wrap items-end gap-3 mb-4">
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
                <form method="POST" action="{{ route('events.checkins.store', $event) }}" class="max-w-2xl space-y-4">
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
                    <flux:button type="submit" variant="primary" icon="check">{{ __('Check in') }}</flux:button>
                </form>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No pending registrations. Scan a ticket QR code to check in.') }}</p>
            @endif
        </div>
    @endcanAs

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2 mb-4">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="space-y-2">
        @forelse ($this->checkins as $checkin)
            <x-event-checkin-card
                wire:key="checkin-{{ $checkin->id }}"
                :checkin="$checkin"
                :event="$event"
            />
        @empty
            <div class="rounded-xl border border-zinc-200 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                {{ __('No check-ins yet.') }}
            </div>
        @endforelse
    </div>

    @if ($this->checkins->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->checkins->links() }}
        </div>
    @endif
</div>
