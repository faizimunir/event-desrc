<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Rule') }}</th>
                    @canAs('bracket_level.read')
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Levels') }}</th>
                    @endcanAs
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($this->brackets as $bracket)
                    @canAs('bracket.update')
                        @can('update', $bracket)
                            <tr
                                role="button"
                                tabindex="0"
                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                onclick="window.location.href='{{ route('events.brackets.edit', [$event, $bracket]) }}'"
                                onkeydown="if (event.key === 'Enter') window.location.href='{{ route('events.brackets.edit', [$event, $bracket]) }}'"
                            >
                        @else
                            <tr>
                        @endcan
                    @else
                        <tr>
                    @endcanAs
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $bracket->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($bracket->gender_rule)
                                {{ $bracket->gender_rule === 'boys' ? __('Boys') : ($bracket->gender_rule === 'girls' ? __('Girls') : '—') }}
                                @if ($bracket->rule_type)
                                    · {{ $bracket->rule_type === 'age' ? __('Age') : __('Birth year') }}
                                    @if ($bracket->isRuleTypeAge() && $bracket->age_min !== null)
                                        {{ $bracket->age_min }}-{{ $bracket->age_max }} ({{ $bracket->age_ref_date?->format('Y-m-d') }})
                                    @elseif ($bracket->isRuleTypeBirthYear() && $bracket->birth_year_start !== null)
                                        {{ $bracket->birth_year_start }}{{ $bracket->birth_year_end ? '-'.$bracket->birth_year_end : '' }}
                                    @endif
                                @endif
                            @elseif ($bracket->rule_type)
                                {{ $bracket->rule_type === 'age' ? __('Age') : __('Birth year') }}
                                @if ($bracket->isRuleTypeAge() && $bracket->age_min !== null)
                                    {{ $bracket->age_min }}-{{ $bracket->age_max }} ({{ $bracket->age_ref_date?->format('Y-m-d') }})
                                @elseif ($bracket->isRuleTypeBirthYear() && $bracket->birth_year_start !== null)
                                    {{ $bracket->birth_year_start }}{{ $bracket->birth_year_end ? '-'.$bracket->birth_year_end : '' }}
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        @canAs('bracket_level.read')
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('events.brackets.bracket-levels.index', [$event, $bracket]) }}" wire:navigate onclick="event.stopPropagation()" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Levels') }}</a>
                        </td>
                        @endcanAs
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->canAs('bracket_level.read') ? 3 : 2 }}" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No brackets found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->brackets->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->brackets->links() }}
        </div>
    @endif
</div>
