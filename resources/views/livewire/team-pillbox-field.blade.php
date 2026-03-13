<div>
    @foreach ($selectedTeamIds as $id)
        <input type="hidden" name="team_ids[]" value="{{ $id }}">
    @endforeach
    <flux:pillbox wire:model.live="selectedTeamIds" label="{{ __('Community / Team / Sponsor') }}" variant="combobox" multiple :filter="false"
        placeholder="{{ __('Search or add team...') }}" required>
        <x-slot name="input">
            <flux:pillbox.input wire:model.live="teamSearch" placeholder="{{ __('Search or add team...') }}" />
        </x-slot>
        @foreach ($this->teams as $team)
            <flux:pillbox.option :value="$team->id">{{ $team->name }}</flux:pillbox.option>
        @endforeach
        <flux:pillbox.option.create wire:click="createTeam" min-length="1">
            {{ __('Create') }} "<span wire:text="teamSearch"></span>"
        </flux:pillbox.option.create>
        <x-slot name="empty">
            <flux:pillbox.option.empty when-loading="{{ __('Loading...') }}">
                {{ __('No team found. Type to search or create.') }}
            </flux:pillbox.option.empty>
        </x-slot>
    </flux:pillbox>
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('One rider can have multiple teams. Type to search or add new name if not in list.') }}</p>
</div>
