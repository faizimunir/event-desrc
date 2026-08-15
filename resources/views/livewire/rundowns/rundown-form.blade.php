<form wire:submit="save" class="max-w-lg space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <flux:input
                wire:model="start_time"
                type="time"
                :label="__('Start time')"
                :min="$minTime"
                :max="$maxTime"
                required
                autofocus
            />
            @error('start_time')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <flux:input
                wire:model="end_time"
                type="time"
                :label="__('End time')"
                :min="$minTime"
                :max="$maxTime"
                required
            />
            @error('end_time')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>
    @if ($timeWindowLabel)
        <p class="-mt-2 text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Must be within the event time: :window', ['window' => $timeWindowLabel]) }}
        </p>
    @endif

    @if ($rundown?->exists)
        <div class="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <flux:heading size="sm">{{ __('Actual time (realtime)') }}</flux:heading>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Recorded from Play/Stop on Live Result, or edit manually here.') }}
                    </p>
                </div>
                @if ($previewRundown && $previewRundown->timingStatus() !== \App\Models\Rundown::TIMING_PENDING)
                    @php
                        $badgeClass = match ($previewRundown->timingStatus()) {
                            \App\Models\Rundown::TIMING_LIVE => 'bg-green-500/10 text-green-600 dark:bg-green-500/15 dark:text-green-400',
                            \App\Models\Rundown::TIMING_ONTIME => 'bg-sky-500/10 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400',
                            \App\Models\Rundown::TIMING_DELAYED => 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
                            default => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400',
                        };
                    @endphp
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide {{ $badgeClass }}">
                        {{ $previewRundown->timingStatusLabel() }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:input
                        wire:model.live="actual_started_at"
                        type="time"
                        :label="__('Actual start')"
                    />
                    @error('actual_started_at')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <flux:input
                        wire:model.live="actual_ended_at"
                        type="time"
                        :label="__('Actual end')"
                    />
                    @error('actual_ended_at')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Leave empty to clear the recorded actual times.') }}
            </p>
        </div>
    @endif

    <div>
        <flux:input
            wire:model="title"
            type="text"
            :label="__('Title (optional)')"
            placeholder="{{ __('e.g. ISHOMA, or leave empty to use bracket names') }}"
        />
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Use a title for non-race slots (e.g. break). If empty, the schedule shows selected bracket names.') }}
        </p>
        @error('title')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <flux:checkbox.group wire:model.live="bracketsSelected" :label="__('Brackets')" variant="buttons">
            @foreach ($brackets as $bracket)
                <flux:checkbox
                    value="{{ $bracket->id }}"
                    :label="$bracket->name"
                    icon="trophy"
                    class="[&:not([data-checked])]:text-zinc-400 dark:[&:not([data-checked])]:text-zinc-500"
                />
            @endforeach
        </flux:checkbox.group>
        @if ($brackets->isEmpty())
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No brackets yet. Create brackets first, or use a title only.') }}</p>
        @else
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Select one or more brackets that race in this time slot.') }}</p>
        @endif
        @error('bracketsSelected')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
        @error('bracketsSelected.*')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    @if ($selectedBrackets->isNotEmpty())
        <div class="space-y-3">
            <div>
                <flux:label>{{ __('Bracket sort order') }}</flux:label>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Lower numbers appear first in the rundown label and live result list.') }}
                </p>
            </div>
            <div class="space-y-2 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                @foreach ($selectedBrackets as $bracket)
                    <div wire:key="bracket-order-{{ $bracket->id }}" class="flex items-center gap-3">
                        <div class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $bracket->name }}
                        </div>
                        <flux:input
                            wire:model="bracketOrders.{{ $bracket->id }}"
                            type="number"
                            min="0"
                            class="w-24!"
                            :aria-label="__('Sort order for :name', ['name' => $bracket->name])"
                        />
                    </div>
                @endforeach
            </div>
            @error('bracketOrders')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            @error('bracketOrders.*')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <flux:button variant="primary" type="submit">
            {{ $rundown?->exists ? __('Update Rundown') : __('Create Rundown') }}
        </flux:button>
        <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'rundown'])" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>
</form>
