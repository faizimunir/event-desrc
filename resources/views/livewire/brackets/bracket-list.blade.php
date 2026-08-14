<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('bracket.create')
            <flux:button
                variant="primary"
                :href="route('events.brackets.create', $event)"
                wire:navigate
                icon="plus"
                square
                class="shrink-0"
                :aria-label="__('Add Bracket')"
            />
        @endcanAs

        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />

        @canAs('bracket.update')
            <flux:button
                type="button"
                variant="outline"
                :icon="$hideAllQuota ? 'eye-slash' : 'eye'"
                square
                class="shrink-0 {{ $hideAllQuota ? '!text-red-600 dark:!text-red-400' : '' }}"
                wire:click="$toggle('hideAllQuota')"
                :aria-label="$hideAllQuota ? __('Show quota for all brackets') : __('Hide quota for all brackets')"
            />
        @endcanAs
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Brackets') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->brackets->total()) }}
        </span>
    </div>

    @if ($this->brackets->isEmpty())
        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="trophy" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No brackets found.') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new bracket.') }}</p>
        </div>
    @else
        <div class="users-list-panel" wire:key="brackets-paged-p{{ $this->brackets->currentPage() }}">
            @foreach ($this->brackets as $bracket)
                @php
                    $genderLabel = match ($bracket->gender_rule) {
                        'boys' => __('Boys'),
                        'girls' => __('Girls'),
                        default => null,
                    };
                    $ruleParts = [];
                    if ($genderLabel) {
                        $ruleParts[] = $genderLabel;
                    }
                    if ($bracket->rule_type) {
                        $ruleParts[] = $bracket->rule_type === 'age' ? __('Age') : __('Birth year');
                        if ($bracket->isRuleTypeAge() && $bracket->age_min !== null) {
                            $ruleParts[] = $bracket->age_min.'-'.$bracket->age_max.($bracket->age_ref_date ? ' ('.$bracket->age_ref_date->format('Y-m-d').')' : '');
                        } elseif ($bracket->isRuleTypeBirthYear() && $bracket->birth_year_start !== null) {
                            $ruleParts[] = $bracket->birth_year_start.($bracket->birth_year_end ? '-'.$bracket->birth_year_end : '');
                        }
                    }
                    $ruleLabel = $ruleParts !== [] ? implode(' · ', $ruleParts) : '—';
                    $canUpdate = auth()->user()->canAs('bracket.update') && auth()->user()->can('update', $bracket);
                @endphp

                <div wire:key="bracket-{{ $bracket->id }}" class="users-list-row group">
                    @if ($canUpdate)
                        <a
                            href="{{ route('events.brackets.edit', [$event, $bracket]) }}"
                            wire:navigate
                            class="flex min-w-0 flex-1 items-center gap-2.5"
                        >
                    @else
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    @endif
                        <div class="users-list-avatar">
                            <flux:icon name="trophy" variant="outline" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                    {{ $bracket->name }}
                                </p>
                                @if ($bracket->hide_quota)
                                    <span class="hidden shrink-0 rounded-full bg-red-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-red-600 sm:inline dark:bg-red-500/15 dark:text-red-400">
                                        {{ __('Quota Hidden') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $ruleLabel }}
                            </p>
                        </div>
                    @if ($canUpdate)
                        </a>
                    @else
                        </div>
                    @endif

                    @canAs('bracket_level.read')
                        <a
                            href="{{ route('events.brackets.bracket-levels.index', [$event, $bracket]) }}"
                            wire:navigate
                            class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 transition hover:bg-orange-500/10 hover:text-orange-600 dark:bg-zinc-700 dark:text-zinc-400 dark:hover:bg-orange-500/15 dark:hover:text-orange-400"
                        >
                            {{ __('Levels') }}
                        </a>
                    @endcanAs

                    @if ($canUpdate)
                        <flux:icon
                            name="chevron-right"
                            variant="mini"
                            class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($this->brackets->hasPages())
        <div class="mt-4 pb-2">
            {{ $this->brackets->links() }}
        </div>
    @endif
</div>
