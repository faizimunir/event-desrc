<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('checkin.create')
            <flux:button
                variant="primary"
                :href="route('events.checkins.create', $event)"
                wire:navigate
                icon="camera"
                square
                class="shrink-0"
                :aria-label="__('Scan check-in')"
            />
        @endcanAs

        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name or nickname…')"
            class="min-w-0 flex-1"
        />

        <flux:dropdown position="bottom" align="end">
            <flux:button
                type="button"
                icon="funnel"
                square
                class="shrink-0 {{ $bracketFilter !== '' ? '!ring-2 !ring-orange-500/50' : '' }}"
                :aria-label="__('Filter by bracket')"
            />

            <flux:menu>
                <flux:menu.item wire:click="setBracketFilter('')">
                    {{ __('All brackets') }}
                </flux:menu.item>

                @foreach ($this->brackets as $bracket)
                    <flux:menu.item wire:click="setBracketFilter('{{ $bracket->id }}')">
                        {{ $bracket->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </div>

    @php
        $stats = $this->checkinStats;
        $showingFilteredRegistrations = $bracketFilter !== '' && trim($search) === '';
    @endphp

    @if (trim($search) !== '')
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Registrations') }}
                </h2>
                @if ($this->selectedBracketLabel)
                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Bracket') }}: {{ $this->selectedBracketLabel }}
                    </p>
                @endif
            </div>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->registrationSearchResults->count()) }}
            </span>
        </div>

        @if ($this->registrationSearchResults->isEmpty())
            <div class="users-list-panel px-4 py-12 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="magnifying-glass" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No registrations found.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or filters.') }}</p>
            </div>
        @else
            <div class="users-list-panel" wire:key="registration-search-{{ md5($search) }}">
                @foreach ($this->registrationSearchResults as $registration)
                    @include('livewire.events.partials.checkin-registration-row', ['registration' => $registration])
                @endforeach
            </div>
        @endif
    @else
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $showingFilteredRegistrations ? __('Registrations') : __('Check-in') }}
                </h2>
                @if ($this->selectedBracketLabel)
                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Bracket') }}: {{ $this->selectedBracketLabel }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    {{ __('Checked in') }} {{ number_format($stats['checked_in']) }}
                </span>
                <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    {{ __('Not checked in') }} {{ number_format($stats['pending']) }}
                </span>
                <span class="rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                    {{ __('Total') }} {{ number_format($stats['total']) }}
                </span>
            </div>
        </div>

        @if ($showingFilteredRegistrations)
            @if ($this->filteredRegistrations->isEmpty())
                <div class="users-list-panel px-4 py-12 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="funnel" class="size-5 text-zinc-400" />
                    </div>
                    <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                        {{ __('No registrations found.') }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Try adjusting your search or filters.') }}
                    </p>
                </div>
            @else
                <div class="users-list-panel" wire:key="filtered-regs-p{{ $this->filteredRegistrations->currentPage() }}-b{{ $bracketFilter }}">
                    @foreach ($this->filteredRegistrations as $registration)
                        @include('livewire.events.partials.checkin-registration-row', ['registration' => $registration])
                    @endforeach
                </div>
            @endif

            @if ($this->filteredRegistrations->hasPages())
                <div class="mt-4 pb-2">
                    {{ $this->filteredRegistrations->links() }}
                </div>
            @endif
        @else
            @if ($this->checkins->isEmpty())
                <div class="users-list-panel px-4 py-12 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="check-badge" class="size-5 text-zinc-400" />
                    </div>
                    <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                        {{ __('No check-ins yet.') }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Search a registration above or scan a ticket to check in.') }}
                    </p>
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
        @endif
    @endif

    <flux:modal
        name="checkin-registration-modal"
        class="max-w-lg"
        wire:model="checkinModalOpen"
        @close="closeCheckinModal"
        focusable
        dismissible
    >
        @if ($this->selectedRegistration)
            @php
                $selected = $this->selectedRegistration;
                $selectedSummary = $selected->checkinSummary();
                $selectedAlreadyCheckedIn = $selected->checkin !== null;
                $canCheckIn = auth()->user()->canAs('checkin.create')
                    && ! $selectedAlreadyCheckedIn;
            @endphp

            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Registration') }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ $selectedSummary['name'] }}
                        @if ($selectedSummary['number_plate'])
                            · #{{ $selectedSummary['number_plate'] }}
                        @endif
                    </flux:text>
                </div>

                <dl class="grid gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selected->status_label }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedSummary['number_plate'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Team') }}</dt>
                        <dd class="text-right font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedSummary['teams'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedSummary['bracket'] ?? '—' }}</dd>
                    </div>
                    @if ($selectedAlreadyCheckedIn)
                        <div class="flex justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Checked in') }}</dt>
                            <dd class="text-right font-medium text-emerald-600 dark:text-emerald-400">
                                {{ $selected->checkin->checked_in_at?->format('d/m/Y H:i') }}
                                @if ($selected->checkin->checkedInByUser?->name)
                                    <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                        {{ $selected->checkin->checkedInByUser->name }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($canCheckIn)
                    <flux:input
                        wire:model="checkinNotes"
                        type="text"
                        :label="__('Notes (optional)')"
                        :placeholder="__('e.g. Gate A')"
                    />
                    @error('checkinNotes')
                        <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                @endif

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                    @if ($canCheckIn)
                        <flux:button
                            type="button"
                            variant="primary"
                            icon="check"
                            wire:click="confirmCheckin"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Check in') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>

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
