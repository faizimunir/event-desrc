<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by title, description, location…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Title') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Start') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('End') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($this->events as $event)
                    <tr
                        role="button"
                        tabindex="0"
                        class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                        onclick="window.location.href='{{ route('events.show', $event) }}'"
                        onkeydown="if (event.key === 'Enter') window.location.href='{{ route('events.show', $event) }}'"
                    >
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event->title }}</td>
                        <td class="px-4 py-3">
                            <flux:badge color="{{ $event->isEffectiveDraft() ? 'zinc' : ($event->isEffectiveOpenRegist() ? 'green' : 'blue') }}" size="sm">{{ $event->effective_status_label }}</flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->start_at->format('d M Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->end_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->location?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No events found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->events->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->events->links() }}
        </div>
    @endif
</div>
