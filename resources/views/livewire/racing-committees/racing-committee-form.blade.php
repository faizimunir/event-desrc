<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center gap-2">
        <flux:button variant="ghost" size="sm" :href="route('racing-committees.index')" wire:navigate icon="arrow-left">
            {{ __('Back') }}
        </flux:button>
    </div>
    <flux:heading>{{ $racingCommittee ? __('Edit Racing Committee') : __('Add Racing Committee') }}</flux:heading>

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
            <flux:file-upload wire:model="photoRc" :label="__('Photo')">
                <flux:file-upload.dropzone
                    heading="Drop files or click to browse"
                    text="JPG, PNG, GIF up to 3MB"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('photoRc')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($photoRc)
                    <flux:file-item
                        :heading="$photoRc->getClientOriginalName()"
                        :image="$photoRc->temporaryUrl()"
                        :size="$photoRc->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removePhotoRc" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($racingCommittee?->photo_rc_url && !$removeExistingPhoto)
                    <flux:file-item
                        :heading="__('Current photo')"
                        :image="$racingCommittee->photo_rc_url"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removeExistingPhotoRc" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($removeExistingPhoto)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Photo will be removed on save. Upload a new one to replace.') }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" type="submit">{{ $racingCommittee ? __('Update') : __('Create') }}</flux:button>
            <flux:button variant="ghost" :href="route('racing-committees.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @if ($racingCommittee)
        @canAs('rc.delete')
            @can('delete', $racingCommittee)
                <form id="delete-rc-form-{{ $racingCommittee->id }}" method="post" action="{{ route('racing-committees.destroy', $racingCommittee) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this racing committee?')) }}')) document.getElementById('delete-rc-form-{{ $racingCommittee->id }}').submit()"
                    >
                        {{ __('Delete Racing Committee') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    @endif
</div>
