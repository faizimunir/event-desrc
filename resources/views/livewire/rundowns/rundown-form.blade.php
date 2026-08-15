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
        <flux:checkbox.group :label="__('Brackets')" variant="buttons">
            @foreach ($brackets as $bracket)
                <flux:checkbox
                    wire:model="bracketsSelected"
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

    <div class="flex flex-wrap items-center gap-2">
        <flux:button variant="primary" type="submit">
            {{ $rundown?->exists ? __('Update Rundown') : __('Create Rundown') }}
        </flux:button>
        <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'rundown'])" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>
</form>
