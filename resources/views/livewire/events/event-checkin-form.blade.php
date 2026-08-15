<div class="flex min-h-0 flex-1 flex-col gap-6">
    @php
        $scannerRegionId = 'event-checkin-scanner-' . $event->id;
        $last = $this->lastScanResult;
        $lastSummary = is_array($last['summary'] ?? null) ? $last['summary'] : null;
    @endphp

    <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-2 lg:gap-6">
        <div class="min-h-0" wire:ignore>
            @include('partials.event-checkin-scanner', [
                'scannerRegionId' => $scannerRegionId,
                'embedded' => true,
            ])
        </div>

        <div class="flex min-h-[280px] flex-col justify-center rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-900/50 sm:min-h-[360px] sm:p-6">
            @if ($last === null)
                <div class="text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="qr-code" class="size-6 text-zinc-400" />
                    </div>
                    <p class="mt-4 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Scan a ticket to check in') }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Successful check-ins will appear here.') }}
                    </p>
                </div>
            @elseif (($last['type'] ?? '') === 'success')
                <div class="space-y-4 text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                        <flux:icon name="check-circle" class="size-8" />
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $lastSummary['name'] ?? __('Checked in') }}
                        </p>
                        <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                            {{ $last['message'] }}
                        </p>
                    </div>
                    @if ($lastSummary)
                        <dl class="mx-auto grid max-w-sm gap-2 rounded-xl border border-zinc-200 bg-white p-4 text-left text-sm dark:border-zinc-700 dark:bg-zinc-800/60">
                            <div class="flex justify-between gap-3">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $lastSummary['number_plate'] ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Team') }}</dt>
                                <dd class="text-right font-medium text-zinc-900 dark:text-zinc-100">{{ $lastSummary['teams'] ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $lastSummary['bracket'] ?? '—' }}</dd>
                            </div>
                        </dl>
                    @endif
                </div>
            @else
                <div class="space-y-3 text-center">
                    <div @class([
                        'mx-auto flex size-14 items-center justify-center rounded-full',
                        'bg-red-500/15 text-red-600 dark:text-red-400' => ($last['type'] ?? '') === 'error',
                        'bg-amber-500/15 text-amber-600 dark:text-amber-400' => ($last['type'] ?? '') !== 'error',
                    ])>
                        <flux:icon
                            :name="($last['type'] ?? '') === 'error' ? 'x-circle' : 'exclamation-triangle'"
                            class="size-8"
                        />
                    </div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $last['message'] }}
                    </p>
                    @if ($lastSummary)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $lastSummary['name'] ?? __('Rider') }}
                            @if (! empty($lastSummary['number_plate']))
                                · #{{ $lastSummary['number_plate'] }}
                            @endif
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div>
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Already checked in') }}
            </h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->recentCheckins->count()) }}
            </span>
        </div>

        @if ($this->recentCheckins->isEmpty())
            <div class="users-list-panel px-4 py-10 text-center">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No check-ins yet.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Scanned tickets will show up here in order.') }}</p>
            </div>
        @else
            <div class="users-list-panel" wire:key="recent-checkins-{{ $this->recentCheckins->first()?->id }}">
                @foreach ($this->recentCheckins as $checkin)
                    @php
                        $summary = $checkin->registration->checkinSummary();
                        $rider = $checkin->registration->rider;
                        $metaParts = array_values(array_filter([
                            $summary['teams'] ?? null,
                            $summary['bracket'] ?? null,
                            $checkin->checked_in_at?->format('d/m/Y H:i:s'),
                            $checkin->checkedInByUser?->name,
                        ]));
                    @endphp

                    <div wire:key="recent-checkin-{{ $checkin->id }}" class="users-list-row">
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 px-1 font-mono text-[10px] font-semibold uppercase leading-none text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                <span class="truncate">{{ $summary['number_plate'] ?? '—' }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $rider?->name ?? __('Rider') }}
                                    </p>
                                    <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-500 dark:text-emerald-400" />
                                </div>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $metaParts !== [] ? implode(' · ', $metaParts) : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
