<div class="flex h-full w-full flex-1 flex-col gap-4">
    <form wire:submit="save" class="max-w-lg space-y-6">
        @if ($canAssignUser)
            <div>
                <flux:label class="mb-2 block">{{ __('Admin user') }}</flux:label>
                <flux:select wire:model="user_id" :placeholder="__('— Select user —')" class="w-full">
                    <flux:select.option value="">{{ __('— No user —') }}</flux:select.option>
                    @foreach ($users as $u)
                        <flux:select.option :value="$u->id">{{ $u->name }} ({{ $u->email }})</flux:select.option>
                    @endforeach
                </flux:select>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('User that can manage this organizer and its events.') }}</p>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <flux:input wire:model="name" type="text" :label="__('Name')" required autofocus />
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="link" type="url" :label="__('Link')" placeholder="https://..." />
        @error('link')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" type="submit">{{ $organizer ? __('Update Organizer') : __('Create Organizer') }}</flux:button>
            <flux:button variant="ghost" :href="route('organizers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @if ($organizer)
        @canAs('organizer.delete')
            @can('delete', $organizer)
                <form id="delete-organizer-form-{{ $organizer->id }}" method="post" action="{{ route('organizers.destroy', $organizer) }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this organizer?')) }}')) document.getElementById('delete-organizer-form-{{ $organizer->id }}').submit()"
                    >
                        {{ __('Delete Organizer') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    @endif
</div>
