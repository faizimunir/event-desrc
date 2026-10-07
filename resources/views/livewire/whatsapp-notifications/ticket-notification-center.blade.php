<div>
    <x-admin-hero-header :heading="__('WhatsApp Notifications')" :subheading="__('E-ticket delivery')">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search rider or plate…')"
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

                @foreach (\App\Services\TicketWhatsappBroadcast::STATES as $state)
                    <flux:menu.item wire:click="setStatusFilter('{{ $state }}')">
                        {{ match ($state) {
                            'not_sent' => __('Not sent'),
                            'failed' => __('Failed'),
                            'queued' => __('Queued'),
                            default => __('Sent'),
                        } }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </x-admin-hero-header>

    <div class="users-hero-content space-y-4 pb-6 pt-3" @if ($this->runActive) wire:poll.10s @endif>
        {{-- Event & device --}}
        <div class="users-list-panel space-y-3 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <flux:select wire:model.live="eventId" :label="__('Event')" class="w-full">
                        @foreach ($this->events as $eventOption)
                            <flux:select.option :value="$eventOption->id">
                                {{ $eventOption->title }}@if ($eventOption->start_at) — {{ $eventOption->start_at->format('d/m/Y') }}@endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @php
                        $device = $this->device;
                        $deviceColor = match ($device['connected']) {
                            true => 'green',
                            false => 'red',
                            default => 'zinc',
                        };
                        $deviceLabel = match ($device['connected']) {
                            true => __('Device connected'),
                            false => __('Device disconnected'),
                            default => __('Device status unknown'),
                        };
                    @endphp
                    <flux:badge :color="$deviceColor" size="sm">{{ $deviceLabel }}</flux:badge>

                    <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="$refresh">
                        {{ __('Refresh') }}
                    </flux:button>
                </div>
            </div>

            @if ($this->events->isEmpty())
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('You have no events yet.') }}</p>
            @endif
        </div>

        @if ($this->selectedEvent && $this->summary)
            @php
                $summary = $this->summary;
                $counts = $summary['counts'];
                $sendable = $summary['sendable'];
                $progress = $this->progress;
            @endphp

            {{-- Summary --}}
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                @foreach ([
                    ['', __('E-tickets'), $counts['total'], 'text-zinc-900 dark:text-zinc-100'],
                    ['not_sent', __('Not sent'), $counts['not_sent'], 'text-amber-600 dark:text-amber-400'],
                    ['failed', __('Failed'), $counts['failed'], 'text-red-600 dark:text-red-400'],
                    ['queued', __('Queued'), $counts['queued'], 'text-sky-600 dark:text-sky-400'],
                    ['sent', __('Sent'), $counts['sent'], 'text-green-600 dark:text-green-400'],
                ] as [$key, $label, $value, $color])
                    <button
                        type="button"
                        wire:click="setStatusFilter('{{ $key }}')"
                        wire:key="summary-{{ $key ?: 'total' }}"
                        class="users-list-panel px-3 py-3 text-left transition hover:ring-1 hover:ring-orange-400/60 {{ $statusFilter === $key ? 'ring-2 ring-orange-500' : '' }}"
                    >
                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                        <p class="mt-1 text-xl font-semibold {{ $color }}">{{ number_format($value) }}</p>
                    </button>
                @endforeach
            </div>

            @if ($summary['no_whatsapp'] > 0)
                <p class="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-300">
                    <flux:icon name="exclamation-triangle" class="size-4 shrink-0" />
                    {{ __(':count participant(s) have no WhatsApp number and will be skipped.', ['count' => number_format($summary['no_whatsapp'])]) }}
                </p>
            @endif

            {{-- Bulk send --}}
            @if ($this->canSend)
                <div class="users-list-panel space-y-3 p-4">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Bulk send') }}</h2>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Messages are queued in small batches and sent one by one with random delays to keep the WhatsApp number safe.') }}
                        </p>
                    </div>

                    @if ($progress && ($this->runActive || ! empty($progress['finished_at'])))
                        @php
                            $total = max(1, (int) $progress['total']);
                            $percent = min(100, (int) round(($progress['processed'] / $total) * 100));
                            $runColor = match ($progress['status']) {
                                'running' => 'sky',
                                'done' => 'green',
                                'cancelled' => 'zinc',
                                default => 'red',
                            };
                            $runLabel = match (true) {
                                $this->cancelRequested => __('Stopping…'),
                                $progress['status'] === 'running' => __('Running'),
                                $progress['status'] === 'done' => __('Finished'),
                                $progress['status'] === 'cancelled' => __('Cancelled'),
                                default => __('Stopped'),
                            };
                        @endphp

                        <div class="space-y-2 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700" wire:key="bulk-progress">
                            <div class="flex items-center justify-between gap-2">
                                <flux:badge :color="$runColor" size="sm">{{ $runLabel }}</flux:badge>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($progress['processed']) }} / {{ number_format($progress['total']) }}
                                    · {{ __('queued') }} {{ number_format($progress['queued']) }}
                                    @if ($progress['skipped'] > 0)
                                        · {{ __('skipped') }} {{ number_format($progress['skipped']) }}
                                    @endif
                                </span>
                            </div>

                            <div class="h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-orange-500 transition-all" style="width: {{ $percent }}%"></div>
                            </div>

                            @if (! empty($progress['reason']))
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $progress['reason'] }}</p>
                            @endif

                            @if ($this->runActive && ! $this->cancelRequested)
                                <flux:button size="sm" variant="ghost" color="red" wire:click="cancelBulk">
                                    {{ __('Stop bulk send') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        @foreach ([
                            ['not_sent', __('Send to not yet sent'), 'paper-airplane', false],
                            ['failed', __('Resend failed'), 'arrow-path', false],
                            ['all', __('Resend to everyone'), 'chat-bubble-left-right', true],
                        ] as [$target, $label, $icon, $needsWarning])
                            @php
                                $count = $sendable[$target];
                                $confirm = __('Queue :count WhatsApp message(s)? Estimated time to deliver all: :time.', [
                                    'count' => number_format($count),
                                    'time' => $this->estimate($count),
                                ]);
                                if ($needsWarning) {
                                    $confirm .= ' '.__('This includes participants who already received their e-ticket.');
                                }
                            @endphp

                            <flux:button
                                size="sm"
                                :icon="$icon"
                                wire:key="bulk-{{ $target }}"
                                wire:click="sendBulk('{{ $target }}')"
                                wire:confirm="{{ $confirm }}"
                                wire:loading.attr="disabled"
                                wire:target="sendBulk"
                                :disabled="$count === 0 || $this->runActive || $device['connected'] === false"
                            >
                                {{ $label }} ({{ number_format($count) }})
                            </flux:button>
                        @endforeach
                    </div>

                    @if ($device['connected'] === false)
                        <p class="text-xs text-red-600 dark:text-red-400">
                            {{ __('WhatsApp device is not connected. Reconnect it before sending in bulk.') }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- Participants --}}
            <div>
                <div class="flex items-center justify-between gap-3 py-2">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Participants') }}</h2>
                        @if ($statusFilter !== '')
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Status') }}: {{ __(ucfirst(str_replace('_', ' ', $statusFilter))) }}
                            </p>
                        @endif
                    </div>

                    <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                        {{ number_format($this->registrations->total()) }}
                    </span>
                </div>

                @if ($this->registrations->isNotEmpty())
                    <div class="users-list-panel" wire:key="wa-registrations-p{{ $this->registrations->currentPage() }}">
                        @foreach ($this->registrations as $registration)
                            @php
                                $log = $this->latestLogs->get($registration->id);
                                $state = $log?->status ?? 'not_sent';
                                $stateColor = match ($state) {
                                    'sent' => 'green',
                                    'failed' => 'red',
                                    'queued' => 'sky',
                                    default => 'amber',
                                };
                                $stateLabel = match ($state) {
                                    'sent' => __('Sent'),
                                    'failed' => __('Failed'),
                                    'queued' => __('Queued'),
                                    default => __('Not sent'),
                                };
                                $rider = $registration->rider;
                                $hasWhatsapp = filled($rider?->user?->whatsapp);
                                $deliveryNote = $state === 'sent'
                                    ? match ($log?->delivery_status) {
                                        'read' => __('Read'),
                                        'delivered' => __('Delivered'),
                                        default => null,
                                    }
                                    : null;
                            @endphp

                            <div wire:key="wa-reg-{{ $registration->id }}" class="users-list-row group !items-start !py-3">
                                <a
                                    href="{{ route('events.registrations.show', [$this->selectedEvent, $registration]) }}"
                                    wire:navigate
                                    class="flex min-w-0 flex-1 items-start gap-2.5"
                                >
                                    <div class="users-list-avatar mt-0.5">
                                        <flux:icon name="chat-bubble-left-right" class="size-4" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                                {{ $rider?->name ?? __('Rider') }}
                                            </p>
                                            <flux:badge :color="$stateColor" size="sm" class="shrink-0">{{ $stateLabel }}</flux:badge>
                                            @if ($deliveryNote)
                                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $deliveryNote }}</span>
                                            @endif
                                        </div>

                                        <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ collect([
                                                $registration->number_plate ?: $rider?->number_plate,
                                                $registration->bracket?->name,
                                                $log ? $log->maskedRecipient() : null,
                                            ])->filter()->implode(' · ') }}
                                        </p>

                                        @if (! $hasWhatsapp)
                                            <p class="mt-1 text-[11px] font-medium text-amber-700 dark:text-amber-300">
                                                {{ __('No WhatsApp number on file.') }}
                                            </p>
                                        @endif

                                        @if ($state === 'failed' && filled($log?->failed_reason))
                                            <p class="mt-1 line-clamp-2 text-[11px] text-red-600 dark:text-red-400">
                                                {{ \Illuminate\Support\Str::limit($log->failed_reason, 160) }}
                                            </p>
                                        @endif

                                        @if ($log)
                                            <p class="mt-1 truncate text-[11px] text-zinc-400 dark:text-zinc-500">
                                                {{ ($state === 'sent' ? ($log->sent_at ?? $log->created_at) : $log->updated_at)->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                    </div>

                                    <flux:icon
                                        name="chevron-right"
                                        variant="mini"
                                        class="mt-1 size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                                    />
                                </a>

                                @if ($this->canSend && $hasWhatsapp && $state !== 'queued')
                                    <div class="flex shrink-0 items-center ps-1">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            wire:click="resendOne({{ $registration->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="resendOne({{ $registration->id }})"
                                        >
                                            {{ $state === 'sent' ? __('Resend') : __('Send') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="users-list-panel px-4 py-12 text-center">
                        <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="chat-bubble-left-right" class="size-5 text-zinc-400" />
                        </div>
                        <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No e-tickets found.') }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or filters.') }}</p>
                    </div>
                @endif

                @if ($this->registrations->hasPages())
                    <div class="mt-4 pb-2">
                        {{ $this->registrations->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
