<x-layouts::app :title="__('Edit Team')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('teams.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Edit Team') }}</flux:heading>

        <form method="post" action="{{ route('teams.update', $team) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $team->name)" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div>
                <flux:label class="mb-2 block">{{ __('Organizer') }}</flux:label>
                <flux:select name="organizer_id" :placeholder="__('Select organizer (optional)')" class="w-full">
                    <flux:select.option value="">{{ __('— None —') }}</flux:select.option>
                    @foreach ($organizers as $organizer)
                        <flux:select.option :value="$organizer->id" :selected="old('organizer_id', $team->organizer_id) == $organizer->id">{{ $organizer->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('organizer_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <flux:input name="type" type="text" :label="__('Type')" :value="old('type', $team->type)" />
            @error('type')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Team') }}</flux:button>
                <flux:button variant="ghost" :href="route('teams.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('team.delete')
            @can('delete', $team)
                <form id="delete-team-form-{{ $team->id }}" method="post" action="{{ route('teams.destroy', $team) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this team?')) }}')) document.getElementById('delete-team-form-{{ $team->id }}').submit()"
                    >
                        {{ __('Delete Team') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
