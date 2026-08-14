<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('checkin.create')
            <flux:button
                variant="primary"
                :href="route('events.checkins.create', $event)"
                wire:navigate
                icon="plus"
                square
                class="shrink-0"
                :aria-label="__('Record check-in')"
            />
        @endcanAs

        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Check-in') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->checkins->total()) }}
        </span>
    </div>

    @if ($this->checkins->isEmpty())
        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="check-badge" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No check-ins yet.') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or record a new check-in.') }}</p>
        </div>
    @else
        <div class="users-list-panel" wire:key="checkins-paged-p{{ $this->checkins->currentPage() }}">
            @foreach ($this->checkins as $checkin)
                @php
                    $canUpdate = auth()->user()->canAs('checkin.update') && auth()->user()->can('update', $checkin);
                    $summary = $checkin->registration->checkinSummary();
                    $rider = $checkin->registration->rider;
                    $metaParts = array_values(array_filter([
                        $summary['teams'] ?? null,
                        $summary['bracket'] ?? null,
                        $checkin->checked_in_at?->format('d/m/Y H:i'),
                        $checkin->checkedInByUser?->name,
                        $checkin->notes,
                    ]));
                @endphp

                <div wire:key="checkin-{{ $checkin->id }}" class="users-list-row group">
                    @if ($canUpdate)
                        <a
                            href="{{ route('events.checkins.edit', [$event, $checkin]) }}"
                            wire:navigate
                            class="flex min-w-0 flex-1 items-center gap-2.5"
                        >
                    @else
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    @endif
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 px-1 font-mono text-[10px] font-semibold uppercase leading-none text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                            <span class="truncate">{{ $checkin->registration->number_plate ?? '—' }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                    {{ $rider?->name ?? __('Rider') }}
                                </p>
                                <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-500 dark:text-emerald-400" />
                            </div>
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $metaParts !== [] ? implode(' · ', $metaParts) : '—' }}
                            </p>
                        </div>

                        @if ($canUpdate)
                            <flux:icon
                                name="chevron-right"
                                variant="mini"
                                class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                            />
                        @endif
                    @if ($canUpdate)
                        </a>
                    @else
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($this->checkins->hasPages())
        <div class="mt-4 pb-2">
            {{ $this->checkins->links() }}
        </div>
    @endif

    @if ($this->editingRegistration)
        @include('registrations.partials.edit-rider-data-modal', [
            'event' => $event,
            'registration' => $this->editingRegistration,
            'modalName' => 'edit-checkin-registration',
            'returnTab' => 'checkin',
            'openOnLoad' => request()->filled('edit_registration'),
        ])
    @endif
</div>
