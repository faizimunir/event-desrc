<x-layouts::app :title="__('Edit Location')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Edit Location')"
            :subheading="__('Locations')"
            :back-href="route('locations.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('locations.update', $location) }}" class="max-w-lg space-y-6">
                @csrf
                @method('PUT')

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $location->name)" required autofocus />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div>
                    <flux:label class="mb-2 block">{{ __('Google Map (URL or embed link)') }}</flux:label>
                    <flux:textarea name="google_map" rows="4" placeholder="https://www.google.com/maps/... or embed iframe src">{{ old('google_map', $location->google_map) }}</flux:textarea>
                    @error('google_map')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Update Location') }}</flux:button>
                    <flux:button variant="ghost" :href="route('locations.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
            @canAs('location.delete')
                @can('delete', $location)
                    <form id="delete-location-form-{{ $location->id }}" method="post" action="{{ route('locations.destroy', $location) }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <flux:button
                            type="button"
                            variant="danger"
                            icon="trash"
                            onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this location?')) }}')) document.getElementById('delete-location-form-{{ $location->id }}').submit()"
                        >
                            {{ __('Delete Location') }}
                        </flux:button>
                    </form>
                @endcan
            @endcanAs
        </div>
    </div>
</x-layouts::app>
