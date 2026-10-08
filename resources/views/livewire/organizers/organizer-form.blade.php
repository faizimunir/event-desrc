<div class="flex h-full w-full flex-1 flex-col gap-4">
    <form wire:submit="save" class="max-w-lg space-y-6">
        @if ($canAssignUser)
            <div>
                <flux:label class="mb-2 block">{{ __('Admin users') }}</flux:label>
                <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    @forelse ($users as $u)
                        <label wire:key="organizer-user-{{ $u->id }}" class="flex cursor-pointer items-center gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                            <input type="checkbox" wire:model="user_ids" value="{{ $u->id }}" class="size-4 rounded border-zinc-300 text-orange-600 focus:ring-orange-500">
                            <span class="min-w-0 truncate">{{ $u->name }} <span class="text-zinc-500 dark:text-zinc-400">({{ $u->email }})</span></span>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No users with the organizer role.') }}</p>
                    @endforelse
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Users that can manage this organizer and its events. You can select more than one.') }}</p>
                @error('user_ids')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @error('user_ids.*')
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
