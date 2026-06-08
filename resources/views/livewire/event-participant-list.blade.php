<div class="registration-shell">
    <div class="registration-header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ __('Participant') }}
                </h2>
                <p class="mt-1.5 max-w-xl text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Riders with confirmed registration.') }}
                </p>
            </div>
            <flux:badge variant="solid" color="zinc" size="sm">
                {{ $this->registrations->total() }} {{ __('Rider') }}
            </flux:badge>
        </div>
    </div>

    <div class="registration-body">
        <div>
            <div class="flex items-start gap-2">
                <span class="registration-step-icon">
                    <flux:icon name="magnifying-glass" class="size-5" />
                </span>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Search & filter') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cari peserta berdasarkan nama, tim, atau nomor plate.') }}</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <flux:input
                    wire:model.live.debounce.400ms="search"
                    type="search"
                    :label="__('Search')"
                    :placeholder="__('Search rider name, nickname, team, or number plate…')"
                />
                <flux:select wire:model.live="bracket" :label="__('Filter bracket')">
                    <option value="">{{ __('All brackets') }}</option>
                    @foreach ($this->bracketOptions as $bracketOption)
                        <option value="{{ $bracketOption['id'] }}">{{ $bracketOption['name'] }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div
            id="event-participants"
            class="participant-table-wrap"
            wire:loading.class="opacity-60"
            wire:target="search, bracket, gotoPage, nextPage, previousPage"
        >
            <table class="participant-table min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/80">
                    <tr>
                        <th class="text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Rider') }}
                        </th>
                        <th class="text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Bracket') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900/60">
                    @forelse ($this->registrations as $participantRegistration)
                        <tr wire:key="participant-{{ $participantRegistration->id }}">
                            <td class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $participantRegistration->rider?->name ?? '—' }}
                                <span class="text-zinc-500 dark:text-zinc-400 block">
                                    {{ $participantRegistration->rider?->nickname ?? '—' }}
                                    ({{ $participantRegistration->rider?->number_plate ?? '—' }})
                                </span>
                                <span class="text-amber-500 dark:text-amber-400 block">
                                    {{ $participantRegistration->rider?->teams->pluck('name')->implode(', ') ?? '—' }}
                                </span>
                            </td>
                            <td class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $participantRegistration->bracket?->name ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                @if (trim($search) !== '' || $bracket !== '')
                                    {{ __('No participants match your search.') }}
                                @else
                                    {{ __('No participants yet.') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->registrations->hasPages())
            <div class="participant-table-pagination">
                {{ $this->registrations->links() }}
            </div>
        @endif
    </div>
</div>
