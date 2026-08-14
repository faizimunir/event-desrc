<x-layouts::app :title="__('Bracket Levels')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', [$event, 'tab' => 'brackets'])" wire:navigate>{{ __('Brackets') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $bracket->name }} — {{ __('Levels') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'brackets'])" wire:navigate icon="arrow-left">
                    {{ __('Back') }}
                </flux:button>
            </div>
            @canAs('bracket_level.create')
                <flux:button variant="primary" :href="route('events.brackets.bracket-levels.create', [$event, $bracket])" wire:navigate icon="plus">
                    {{ __('Add Bracket Level') }}
                </flux:button>
            @endcanAs
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Level') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name original') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse ($bracket->bracketLevels as $bracketLevel)
                        @canAs('bracket_level.update')
                            @can('update', $bracketLevel)
                                <tr
                                    role="button"
                                    tabindex="0"
                                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                    onclick="window.location.href='{{ route('events.brackets.bracket-levels.edit', [$event, $bracket, $bracketLevel]) }}'"
                                    onkeydown="if (event.key === 'Enter') window.location.href='{{ route('events.brackets.bracket-levels.edit', [$event, $bracket, $bracketLevel]) }}'"
                                >
                            @else
                                <tr>
                            @endcan
                        @else
                            <tr>
                        @endcanAs
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $bracketLevel->level?->name ?? $bracketLevel->event_level_id }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $bracketLevel->name_original }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No bracket levels found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
