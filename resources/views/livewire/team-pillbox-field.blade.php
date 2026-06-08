<div>
    @foreach ($selectedTeamIds as $id)
        <input type="hidden" name="team_ids[]" value="{{ $id }}">
    @endforeach
    <input type="hidden" name="team_search_pending" wire:model="teamSearch">
    <flux:label class="mb-2 block">{{ $fieldLabel }} <span class="text-red-500">*</span></flux:label>
    <flux:pillbox
        wire:model.live="selectedTeamIds"
        variant="combobox"
        multiple
        :filter="false"
        placeholder="{{ __('Search or add team...') }}"
    >
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
    @error('team_ids')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Required. Search from the list or type a new name and click Create to add it.') }}</p>
</div>
