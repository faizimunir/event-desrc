<div class="flex h-full w-full flex-1 flex-col gap-4">
    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:input wire:model="name" type="text" :label="__('Name')" required autofocus />
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="link" type="url" :label="__('Link')" :placeholder="'https://...'" />
        @error('link')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <div>
            <flux:file-upload wire:model="avatarMc" :label="__('Avatar')">
                <flux:file-upload.dropzone
                    heading="Drop files or click to browse"
                    text="JPG, PNG, GIF up to 10MB"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('avatarMc')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($avatarMc)
                    <flux:file-item
                        :heading="$avatarMc->getClientOriginalName()"
                        :image="$avatarMc->temporaryUrl()"
                        :size="$avatarMc->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removeAvatarMc" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($masterOfCeremony?->avatar_mc_url && !$removeExistingAvatar)
                    <flux:file-item
                        :heading="__('Current avatar')"
                        :image="$masterOfCeremony->avatar_mc_url"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removeExistingAvatarMc" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($removeExistingAvatar)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avatar will be removed on save. Upload a new one to replace.') }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" type="submit">{{ $masterOfCeremony ? __('Update') : __('Create') }}</flux:button>
            <flux:button variant="ghost" :href="route('master-of-ceremonies.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @if ($masterOfCeremony)
        @canAs('mc.delete')
            @can('delete', $masterOfCeremony)
                <form id="delete-mc-form-{{ $masterOfCeremony->id }}" method="post" action="{{ route('master-of-ceremonies.destroy', $masterOfCeremony) }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this master of ceremony?')) }}')) document.getElementById('delete-mc-form-{{ $masterOfCeremony->id }}').submit()"
                    >
                        {{ __('Delete') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    @endif
</div>
