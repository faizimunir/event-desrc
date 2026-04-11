<div>
    <div class="mb-4 flex flex-row gap-3 items-center flex-wrap">
        @canAs('event.update')
        <flux:button variant="primary" :href="route('events.registrations.create', $event)" wire:navigate icon="plus">
            {{ __('Add registration') }}
        </flux:button>
        @endcanAs
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
        <div class="flex">
            <flux:modal.trigger name="event-registrations-filter-modal">
                <flux:button
                    icon="funnel"
                    variant="primary"
                    color="sky"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'event-registrations-filter-modal')"
                >
                    {{ __('Filter') }}
                    @if (count($statusFilter) > 0 || count($paymentStatusFilter) > 0)
                        <flux:badge color="zinc" size="xs" class="ml-1">{{ count($statusFilter) + count($paymentStatusFilter) }}</flux:badge>
                    @endif
                </flux:button>
            </flux:modal.trigger>
            <flux:modal name="event-registrations-filter-modal" focusable class="max-w-sm" dismissible>
                <div class="space-y-3 p-2">
                    <flux:heading size="lg">{{ __('Filter') }}</flux:heading>
                    <div class="space-y-3">
                        <flux:pillbox wire:model.live="statusFilter" multiple :label="__('Registration status')" :placeholder="__('All')" class="w-full">
                            <flux:pillbox.option value="pending">{{ __('Pending') }}</flux:pillbox.option>
                            <flux:pillbox.option value="approved">{{ __('Approved') }}</flux:pillbox.option>
                            <flux:pillbox.option value="rejected">{{ __('Rejected') }}</flux:pillbox.option>
                            <flux:pillbox.option value="cancelled">{{ __('Cancelled') }}</flux:pillbox.option>
                        </flux:pillbox>
                        <flux:pillbox wire:model.live="paymentStatusFilter" multiple :label="__('Payment status')" :placeholder="__('All')" class="w-full">
                            <flux:pillbox.option value="pending">{{ __('Pending') }}</flux:pillbox.option>
                            <flux:pillbox.option value="success">{{ __('Success') }}</flux:pillbox.option>
                            <flux:pillbox.option value="failed">{{ __('Failed') }}</flux:pillbox.option>
                            <flux:pillbox.option value="expired">{{ __('Expired') }}</flux:pillbox.option>
                            <flux:pillbox.option value="cancelled">{{ __('Cancelled') }}</flux:pillbox.option>
                            <flux:pillbox.option value="none">{{ __('No payment') }}</flux:pillbox.option>
                        </flux:pillbox>
                    </div>
                    <div class="flex justify-end gap-2">
                        <flux:button type="button" size="sm" wire:click="resetFilters">
                            {{ __('Reset') }}
                        </flux:button>
                        <flux:modal.close>
                            <flux:button variant="primary" size="sm">{{ __('Tutup') }}</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        </div>
        <flux:button variant="outline" :href="$this->exportUrl" target="_blank" rel="noopener" icon="arrow-down-tray">
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        @if ($this->registrations->isEmpty())
            <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No registrations yet.') }}</p>
        @else
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Rider') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Pack') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Order') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @foreach ($this->registrations as $reg)
                        <tr
                            role="button"
                            tabindex="0"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                            onclick="window.location.href='{{ route('events.registrations.show', [$event, $reg]) }}'"
                            onkeydown="if (event.key === 'Enter') window.location.href='{{ route('events.registrations.show', [$event, $reg]) }}'"
                        >
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                <span class="font-medium">{{ $reg->rider->name }}</span>
                                @if ($reg->rider->nickname)
                                    <span class="block text-zinc-500 dark:text-zinc-400">{{ $reg->rider->nickname }}</span>
                                @endif
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $reg->rider->dob?->format('Y') }} {{ $reg->rider->gender_label ?? $reg->rider->gender }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reg->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $reg->bracket->name }}
                            <span class="block text-sm">{{ $reg->package?->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColor = match ($reg->status) {
                                        'approved' => 'green',
                                        'pending' => 'yellow',
                                        'rejected' => 'red',
                                        'cancelled' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$badgeColor" size="sm">
                                    {{ $reg->status_label }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                @if ($reg->order)
                                    @php
                                        $orderColor = match ($reg->order->status) {
                                            'confirmed', 'completed' => 'green',
                                            'draft' => 'yellow',
                                            'pending' => 'yellow',
                                            'cancelled' => 'zinc',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge :color="$orderColor" size="sm">{{ $reg->order->status_label }}</flux:badge>
                                @else
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-2">
                {{ $this->registrations->links() }}
            </div>
        @endif
    </div>
</div>
