<x-layouts::app :title="__('Edit Reward')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Edit Reward')"
            :subheading="__('Rewards')"
            :back-href="route('rewards.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('rewards.update', $reward) }}" class="max-w-lg space-y-6">
                @csrf
                @method('PUT')

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $reward->name)" required autofocus />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="icon" type="text" :label="__('Icon')" :value="old('icon', $reward->icon)" placeholder="e.g. trophy, award, or URL" />
                @error('icon')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Update Reward') }}</flux:button>
                    <flux:button variant="ghost" :href="route('rewards.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
            @canAs('reward.delete')
                @can('delete', $reward)
                    <form id="delete-reward-form-{{ $reward->id }}" method="post" action="{{ route('rewards.destroy', $reward) }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <flux:button
                            type="button"
                            variant="danger"
                            icon="trash"
                            onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this reward?')) }}')) document.getElementById('delete-reward-form-{{ $reward->id }}').submit()"
                        >
                            {{ __('Delete Reward') }}
                        </flux:button>
                    </form>
                @endcan
            @endcanAs
        </div>
    </div>
</x-layouts::app>
