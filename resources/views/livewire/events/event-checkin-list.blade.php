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
                        <flux:select name="registration_id" :label="__('Registration')" required>
                            <option value="">{{ __('Select participant…') }}</option>
                            @foreach ($this->registrationsAvailableForCheckin as $reg)
                                <option value="{{ $reg->id }}">
                                    {{ $reg->rider?->name ?? __('Rider') }} — {{ $reg->number_plate ?? '—' }}
                                </option>
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

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Participant') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('No. plate') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Checked in at') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('By') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</th>
                    @if(auth()->user()->canAs('checkin.update') || auth()->user()->canAs('checkin.delete'))
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($this->checkins as $checkin)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $checkin->registration->rider?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $checkin->registration->number_plate ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $checkin->checked_in_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $checkin->checkedInByUser?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $checkin->notes ?? '—' }}</td>
                        @if(auth()->user()->canAs('checkin.update') || auth()->user()->canAs('checkin.delete'))
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('update', $checkin)
                                <flux:button size="sm" variant="ghost" x-data x-on:click="$dispatch('open-modal', 'edit-checkin-{{ $checkin->id }}')">{{ __('Edit') }}</flux:button>
                                <flux:modal :name="'edit-checkin-'.$checkin->id" focusable class="max-w-md" dismissible>
                                    <form method="POST" action="{{ route('events.checkins.update', [$event, $checkin]) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="space-y-3 p-2">
                                            <flux:heading size="lg">{{ __('Edit check-in') }}</flux:heading>
                                            <flux:input name="notes" type="text" :label="__('Notes')" :value="$checkin->notes" />
                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                                </flux:modal.close>
                                                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                                            </div>
                                        </div>
                                    </form>
                                </flux:modal>
                            @endcan
                            @canAs('checkin.delete')
                                @can('delete', $checkin)
                                    <form method="POST" action="{{ route('events.checkins.destroy', [$event, $checkin]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this check-in?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="ghost" size="sm" color="red">{{ __('Remove') }}</flux:button>
                                    </form>
                                @endcan
                            @endcanAs
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (auth()->user()->canAs('checkin.update') || auth()->user()->canAs('checkin.delete')) ? 6 : 5 }}" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No check-ins yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->checkins->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->checkins->links() }}
        </div>
    @endif
</div>
