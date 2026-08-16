@php
    $summary = $registration->checkinSummary();
    $rider = $registration->rider;
    $alreadyCheckedIn = $registration->checkin !== null;
    $metaParts = array_values(array_filter([
        $summary['teams'] ?? null,
        $summary['bracket'] ?? null,
        $registration->status_label,
        $alreadyCheckedIn
            ? ($registration->checkin->checked_in_at?->format('d/m/Y H:i') ?? __('Checked in'))
            : __('Not checked in'),
    ]));
@endphp

<button
    type="button"
    wire:key="registration-row-{{ $registration->id }}"
    wire:click="openRegistrationCheckin({{ $registration->id }})"
    class="users-list-row group w-full text-left"
>
    <div class="flex min-w-0 flex-1 items-center gap-2.5">
        <div @class([
            'flex size-9 shrink-0 items-center justify-center rounded-xl px-1 font-mono text-[10px] font-semibold uppercase leading-none',
            'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $alreadyCheckedIn,
            'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => ! $alreadyCheckedIn,
        ])>
            <span class="truncate">{{ $summary['number_plate'] ?? '—' }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                    {{ $rider?->name ?? __('Rider') }}
                </p>
                @if ($alreadyCheckedIn)
                    <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-500 dark:text-emerald-400" />
                @endif
            </div>
            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                {{ $metaParts !== [] ? implode(' · ', $metaParts) : '—' }}
            </p>
        </div>

        <flux:icon
            name="chevron-right"
            variant="mini"
            class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
        />
    </div>
</button>
