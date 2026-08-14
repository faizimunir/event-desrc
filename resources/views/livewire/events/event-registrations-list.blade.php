<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('event.update')
            <flux:button
                variant="primary"
                :href="route('events.registrations.create', $event)"
                wire:navigate
                icon="plus"
                square
                class="shrink-0"
                :aria-label="__('Add registration')"
            />
        @endcanAs

        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />

        <div class="flex shrink-0 items-center gap-2">
            <div class="relative shrink-0">
                <flux:modal.trigger name="event-registrations-filter-modal">
                    <flux:button
                        icon="funnel"
                        variant="primary"
                        color="sky"
                        square
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'event-registrations-filter-modal')"
                        :aria-label="__('Filter')"
                    />
                </flux:modal.trigger>
                @if (count($statusFilter) > 0 || count($paymentStatusFilter) > 0 || count($bracketFilter) > 0)
                    <span class="pointer-events-none absolute -right-1 -top-1 z-10 flex size-4 items-center justify-center rounded-full bg-sky-700 text-[10px] font-bold text-white">
                        {{ count($statusFilter) + count($paymentStatusFilter) + count($bracketFilter) }}
                    </span>
                @endif
            </div>

            @canAs('registration.delete')
                <div class="relative shrink-0">
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        square
                        :disabled="count($selectedRegistrationIds) === 0"
                        x-on:click="if (confirm({{ json_encode(__('Delete selected registrations? This cannot be undone.')) }})) { $wire.deleteSelectedRegistrations() }"
                        :aria-label="__('Delete selected')"
                    />
                    @if (count($selectedRegistrationIds) > 0)
                        <span class="pointer-events-none absolute -right-1 -top-1 z-10 flex size-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white">
                            {{ count($selectedRegistrationIds) }}
                        </span>
                    @endif
                </div>
            @endcanAs

            <flux:button
                variant="outline"
                :href="$this->exportUrl"
                target="_blank"
                rel="noopener"
                icon="arrow-down-tray"
                square
                :aria-label="__('Export')"
            />
        </div>
    </div>

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
                @if ($event->brackets_sorted_for_display->isNotEmpty())
                    <flux:pillbox wire:model.live="bracketFilter" multiple :label="__('Bracket')" :placeholder="__('All')" class="w-full">
                        @foreach ($event->brackets_sorted_for_display as $bracket)
                            <flux:pillbox.option value="{{ (string) $bracket->id }}">{{ $bracket->name }}</flux:pillbox.option>
                        @endforeach
                    </flux:pillbox>
                @endif
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

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Registrations') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->registrations->total()) }}
        </span>
    </div>

    @if ($this->registrations->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50/80 px-4 py-12 text-center dark:border-zinc-700 dark:bg-zinc-800/30">
            <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                <flux:icon name="clipboard-document-list" class="size-6 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No registrations yet.') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or filters.') }}</p>
        </div>
    @else
        <div class="flex flex-col gap-2">
            @foreach ($this->registrations as $reg)
                @php
                    $badgeColor = match ($reg->status) {
                        'approved' => 'green',
                        'pending' => 'yellow',
                        'rejected' => 'red',
                        'cancelled' => 'zinc',
                        default => 'zinc',
                    };
                    $orderColor = $reg->order ? match ($reg->order->status) {
                        'paid', 'completed' => 'green',
                        'draft', 'unpaid' => 'yellow',
                        'cancelled' => 'zinc',
                        default => 'zinc',
                    } : null;
                @endphp

                <div
                    wire:key="registration-{{ $reg->id }}"
                    class="group flex items-center gap-2 overflow-hidden rounded-xl border border-zinc-200/80 bg-white py-3.5 pl-3 pr-3 transition duration-200 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700/80 dark:bg-zinc-800/50 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 sm:gap-3 sm:pl-4 sm:pr-4"
                >
                    @canAs('registration.delete')
                        <div class="shrink-0" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                            <flux:checkbox wire:model.live="selectedRegistrationIds" :value="$reg->id" />
                        </div>
                    @endcanAs

                    <a
                        href="{{ route('events.registrations.show', [$event, $reg]) }}"
                        wire:navigate
                        class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4"
                    >
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-orange-500/10 text-sm font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                            {{ strtoupper(mb_substr($reg->rider->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1.5">
                                <div class="min-w-0">
                                    <h3 class="truncate font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $reg->rider->name }}
                                    </h3>
                                    @if ($reg->rider->nickname)
                                        <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $reg->rider->nickname }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <flux:badge :color="$badgeColor" size="sm">
                                        {{ $reg->status_label }}
                                    </flux:badge>
                                    @if ($reg->order)
                                        <flux:badge :color="$orderColor" size="sm">
                                            {{ $reg->order->status_label }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex min-w-0 items-center gap-1.5">
                                    <flux:icon name="trophy" class="size-3.5 shrink-0" />
                                    <span class="truncate">{{ $reg->bracket->name }}</span>
                                </span>
                                <span class="inline-flex min-w-0 items-center gap-1.5">
                                    <flux:icon name="cube" class="size-3.5 shrink-0" />
                                    <span class="truncate">{{ $reg->package?->name ?? '—' }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon name="calendar-days" class="size-3.5 shrink-0" />
                                    {{ $reg->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @if ($this->registrations->hasPages())
            <div class="mt-4 flex justify-center">
                {{ $this->registrations->links() }}
            </div>
        @endif
    @endif
</div>
