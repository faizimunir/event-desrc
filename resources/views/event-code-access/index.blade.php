<x-layouts::app :title="__('Early access codes') . ' — ' . $event->title">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Early access codes') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', $event)" wire:navigate icon="arrow-left">
                {{ __('Back to event') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Early access codes') }}</flux:heading>
        <flux:subheading>{{ __('Share these codes to allow early registration before registration opens.') }}</flux:subheading>

        @if (session('status'))
            <flux:callout variant="success" class="rounded-lg">{{ session('status') }}</flux:callout>
        @endif

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">{{ __('Add code') }}</h2>
            <form method="POST" action="{{ route('events.code-access.store', $event) }}" class="max-w-xl space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input name="code" type="text" :label="__('Code')" :placeholder="__('e.g. EARLY2025')" required />
                    <flux:input name="name" type="text" :label="__('Name (optional)')" :placeholder="__('e.g. Early Bird')" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input name="valid_from" type="datetime-local" :label="__('Valid from (optional)')" />
                    <flux:input name="valid_until" type="datetime-local" :label="__('Valid until (optional)')" />
                </div>
                <flux:input name="usage_limit" type="number" min="1" :label="__('Usage limit (optional)')" :placeholder="__('Max uses, leave empty for unlimited')" />
                <flux:button type="submit" variant="primary">{{ __('Add code') }}</flux:button>
            </form>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
            <h2 class="p-4 text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">{{ __('Existing codes') }}</h2>
            @if ($codes->isEmpty())
                <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No access codes yet.') }}</p>
            @else
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Code') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Used') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Valid') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach ($codes as $ca)
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono text-zinc-900 dark:text-zinc-100">{{ $ca->code }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $ca->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $ca->times_used }}@if($ca->usage_limit) / {{ $ca->usage_limit }}@endif</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    @if ($ca->valid_from || $ca->valid_until)
                                        {{ $ca->valid_from?->format('d/m/Y H:i') ?? '—' }} → {{ $ca->valid_until?->format('d/m/Y H:i') ?? '—' }}
                                    @else
                                        {{ __('Always') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('events.code-access.destroy', [$event, $ca]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this code?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="ghost" size="sm" color="red">{{ __('Remove') }}</flux:button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts::app>
