<div>
    @foreach ($selectedOrganizerIds as $id)
        <input type="hidden" name="organizer_ids[]" value="{{ $id }}">
    @endforeach
    <flux:label class="mb-2 block">{{ __('Community / Team') }}</flux:label>
    <flux:pillbox wire:model.live="selectedOrganizerIds" variant="combobox" multiple :filter="false"
        placeholder="{{ __('Search or add team...') }}"
    >
        <x-slot name="input">
            <flux:pillbox.input wire:model.live="organizerSearch" placeholder="{{ __('Search or add team...') }}" />
        </x-slot>
        @foreach ($this->organizers as $organizer)
            <flux:pillbox.option :value="$organizer->id">{{ $organizer->name }}</flux:pillbox.option>
        @endforeach
        <flux:pillbox.option.create wire:click="createOrganizer" min-length="1">
            {{ __('Create') }} "<span wire:text="organizerSearch"></span>"
        </flux:pillbox.option.create>
        <x-slot name="empty">
            <flux:pillbox.option.empty when-loading="{{ __('Loading...') }}">
                {{ __('No team found. Type to search or create.') }}
            </flux:pillbox.option.empty>
        </x-slot>
    </flux:pillbox>
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('One rider can have multiple teams. Type to search; add new name if not in list.') }}</p>
</div>
