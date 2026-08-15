<div>
    <x-admin-hero-header :heading="__('Payments Management')">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search…')"
            class="min-w-0 flex-1"
        />

        <flux:dropdown position="bottom" align="end">
            <flux:button
                type="button"
                icon="funnel"
                square
                class="users-hero-action shrink-0 {{ $statusFilter !== '' ? '!ring-2 !ring-white/50' : '' }}"
                :aria-label="__('Filter by status')"
            />

            <flux:menu>
                <flux:menu.item wire:click="setStatusFilter('')">
                    {{ __('All statuses') }}
                </flux:menu.item>

                @foreach (\App\Models\Payment::STATUSES as $status)
                    <flux:menu.item wire:click="setStatusFilter('{{ $status }}')">
                        {{ __(ucfirst($status)) }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </x-admin-hero-header>

    <div class="users-hero-content pb-6">
        <div class="flex items-center justify-between gap-3 py-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('All payments') }}
                </h2>
                @if ($statusFilter !== '')
                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Status') }}: {{ __(ucfirst($statusFilter)) }}
                    </p>
                @endif
            </div>

            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->payments->total()) }}
            </span>
        </div>

        @if ($this->payments->isNotEmpty())
            <div class="users-list-panel" wire:key="payments-paged-p{{ $this->payments->currentPage() }}">
                @foreach ($this->payments as $payment)
                    @php
                        $badgeColor = match ($payment->status) {
                            'success' => 'green',
                            'pending', 'submitted' => 'yellow',
                            'failed' => 'red',
                            'void', 'refunded', 'expired', 'cancelled' => 'zinc',
                            default => 'zinc',
                        };
                        $registration = $payment->registration;
                        $event = $registration?->event;
                        $rider = $registration?->rider;
                    @endphp

                    <div wire:key="payment-{{ $payment->id }}" class="users-list-row group !items-start !py-3">
                        <a
                            @if ($event && $registration)
                                href="{{ route('events.registrations.show', [$event, $registration]) }}"
                                wire:navigate
                            @endif
                            class="flex min-w-0 flex-1 items-start gap-2.5"
                        >
                            <div class="users-list-avatar mt-0.5">
                                <flux:icon name="banknotes" class="size-4" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $event?->title ?? __('Event') }}
                                    </p>
                                    <flux:badge :color="$badgeColor" size="sm" class="shrink-0">
                                        {{ $payment->status_label }}
                                    </flux:badge>
                                </div>

                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ collect([
                                        $rider?->name,
                                        $registration?->bracket?->name,
                                        $payment->formatted_transfer_amount ?? $payment->formatted_amount,
                                    ])->filter()->implode(' · ') }}
                                </p>

                                <p class="mt-1 truncate text-[11px] text-zinc-400 dark:text-zinc-500">
                                    {{ $payment->created_at->format('d/m/Y H:i') }}
                                    @if ($payment->reviewed_at)
                                        · {{ __('Reviewed') }} {{ $payment->reviewed_at->format('d/m/Y H:i') }}
                                    @endif
                                </p>

                                @if ($payment->method === 'manual' && $payment->transfer_amount && $payment->isPending())
                                    <p class="mt-1 text-[11px] font-medium text-amber-700 dark:text-amber-300">
                                        {{ __('Transfer') }}: {{ $payment->formatted_transfer_amount }}
                                        @if ($payment->manualUniqueSuffixFormatted())
                                            · {{ __('Code') }} {{ $payment->manualUniqueSuffixFormatted() }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            <flux:icon
                                name="chevron-right"
                                variant="mini"
                                class="mt-1 size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                            />
                        </a>

                        <div class="flex shrink-0 flex-col items-end gap-1.5 ps-1" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                            @if ($payment->transfer_proof_url)
                                <a
                                    href="{{ $payment->transfer_proof_url }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-xs font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                                >
                                    {{ __('Proof') }}
                                </a>
                            @endif

                            @if ($payment->isPending() || $payment->isSubmitted())
                                <form action="{{ route('payments.approve', $payment) }}" method="post">
                                    @csrf
                                    <flux:button variant="ghost" size="sm" type="submit" color="green">{{ __('Approve') }}</flux:button>
                                </form>
                                <form action="{{ route('payments.reject', $payment) }}" method="post" x-data="{ open: false }">
                                    @csrf
                                    <flux:button variant="ghost" size="sm" type="button" color="red" @click="open = !open">{{ __('Reject') }}</flux:button>
                                    <div x-show="open" x-cloak class="mt-1 w-44 text-left">
                                        <label for="reject-notes-{{ $payment->id }}" class="block text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Notes (optional)') }}</label>
                                        <textarea name="admin_notes" id="reject-notes-{{ $payment->id }}" rows="2" class="mt-1 block w-full rounded border border-zinc-300 bg-white text-xs dark:border-zinc-600 dark:bg-zinc-800"></textarea>
                                        <flux:button variant="ghost" size="sm" type="submit" color="red" class="mt-1">{{ __('Confirm reject') }}</flux:button>
                                    </div>
                                </form>
                                <form action="{{ route('payments.expire', $payment) }}" method="post" onsubmit="return confirm('{{ __('Mark as expired and create new order ID for this registration? Old order ID will no longer work.') }}');">
                                    @csrf
                                    <flux:button variant="ghost" size="sm" type="submit" color="zinc">{{ __('Expire') }}</flux:button>
                                </form>
                            @elseif ($payment->admin_notes)
                                <span class="max-w-28 truncate text-[11px] text-zinc-500 dark:text-zinc-400" title="{{ $payment->admin_notes }}">{{ __('Has notes') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="users-list-panel px-4 py-12 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="banknotes" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No payments found.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or filters.') }}</p>
            </div>
        @endif

        @if ($this->payments->hasPages())
            <div class="mt-4 pb-2">
                {{ $this->payments->links() }}
            </div>
        @endif
    </div>
</div>
