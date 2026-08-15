<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center gap-2">
        <flux:button variant="ghost" size="sm" :href="$this->returnUrl()" wire:navigate icon="arrow-left">
            {{ __('Back') }}
        </flux:button>
    </div>
    <flux:heading>{{ $rider ? __('Edit Rider') : __('Add Rider') }}</flux:heading>

    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:input wire:model="name" type="text" :label="__('Name')" required autofocus />
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="nickname" type="text" :label="__('Nickname')" />
        @error('nickname')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="pob" type="text" :label="__('Place of birth')" />
        @error('pob')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="dob" type="date" :label="__('Date of birth')" />
        @error('dob')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <div>
            <flux:label class="mb-2 block">{{ __('Gender') }}</flux:label>
            <flux:select wire:model="gender" :placeholder="__('Select gender')">
                <option value="">{{ __('— Select —') }}</option>
                <option value="boys">{{ __('Boys') }}</option>
                <option value="girls">{{ __('Girls') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </flux:select>
            @error('gender')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <flux:input wire:model="number_plate" type="text" :label="__('Number plate')" />
        @error('number_plate')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        {{-- Photo rider --}}
        <div>
            <flux:file-upload wire:model="photoRider" :label="__('Photo rider')">
                <flux:file-upload.dropzone
                    heading="Drop files or click to browse"
                    text="{{ __('JPG, PNG, WebP up to :max KB', ['max' => config('media.max_upload_size_kb', 2048)]) }}"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('photoRider')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($photoRider)
                    <flux:file-item
                        :heading="$photoRider->getClientOriginalName()"
                        :image="$photoRider->temporaryUrl()"
                        :size="$photoRider->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removePhotoRider" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($rider?->getFirstMediaUrl('photo_rider'))
                    <flux:file-item
                        :heading="__('Current photo rider')"
                        :image="$rider->getFirstMediaUrl('photo_rider', 400)"
                    >
                        <x-slot name="actions">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Replace by uploading above') }}</span>
                        </x-slot>
                    </flux:file-item>
                @endif
            </div>
        </div>

        {{-- Photo KIA --}}
        <div>
            <flux:file-upload wire:model="photoKia" :label="__('Photo KIA (Kartu Identitas Anak)')">
                <flux:file-upload.dropzone
                    heading="Drop files or click to browse"
                    text="{{ __('JPG, PNG, WebP up to :max KB', ['max' => config('media.max_upload_size_kb', 2048)]) }}"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('photoKia')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($photoKia)
                    <flux:file-item
                        :heading="$photoKia->getClientOriginalName()"
                        :image="$photoKia->temporaryUrl()"
                        :size="$photoKia->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removePhotoKia" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($rider?->getFirstMediaUrl('photo_kia'))
                    <flux:file-item
                        :heading="__('Current photo KIA')"
                        :image="$rider->getFirstMediaUrl('photo_kia', 400)"
                    >
                        <x-slot name="actions">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Replace by uploading above') }}</span>
                        </x-slot>
                    </flux:file-item>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" type="submit">{{ $rider ? __('Update Rider') : __('Create Rider') }}</flux:button>
            <flux:button variant="ghost" :href="$this->returnUrl()" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @if ($rider)
        @canAs('rider.delete')
            @can('delete', $rider)
                <form id="delete-rider-form-{{ $rider->id }}" method="post" action="{{ route('riders.destroy', $rider) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this rider?')) }}')) document.getElementById('delete-rider-form-{{ $rider->id }}').submit()"
                    >
                        {{ __('Delete Rider') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    @endif
</div>
