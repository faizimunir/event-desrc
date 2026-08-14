<x-layouts::app :title="__('Edit Track')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'tracks'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Edit Track') }}</flux:heading>

        <form method="post" action="{{ route('events.tracks.update', [$event, $track]) }}" enctype="multipart/form-data" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $track->name)" placeholder="{{ __('e.g. Main circuit') }}" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="material" type="text" :label="__('Material')" :value="old('material', $track->material)" placeholder="{{ __('e.g. Dirt, Asphalt') }}" />
            @error('material')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="long_track" type="text" :label="__('Track length')" :value="old('long_track', $track->long_track)" placeholder="{{ __('e.g. 1.2 km, 500 m') }}" />
            @error('long_track')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div>
                <flux:label class="mb-2 block">{{ __('Photo track') }}</flux:label>
                @if ($track->photoTrackUrl())
                    <div class="mb-2">
                        <img src="{{ $track->photoTrackUrl() }}" alt="" class="h-24 w-auto rounded object-cover" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Current photo. Upload a new file to replace.') }}</p>
                    </div>
                @endif
                <input type="file" name="photo_track" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                @error('photo_track')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Track') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'tracks'])" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('track.delete')
            @can('delete', $track)
                <form id="delete-track-form-{{ $track->id }}" method="post" action="{{ route('events.tracks.destroy', [$event, $track]) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this track?')) }}')) document.getElementById('delete-track-form-{{ $track->id }}').submit()"
                    >
                        {{ __('Delete Track') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
