<x-layouts::app :title="__('Add User')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add User')"
            :subheading="__('Users')"
            :back-href="route('users.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('users.store') }}" class="max-w-lg space-y-6">
                @csrf

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required autofocus />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="email" type="email" :label="__('Email')" :value="old('email')" />
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="whatsapp" type="text" :label="__('WhatsApp')" :value="old('whatsapp')" />
                @error('whatsapp')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="password" type="password" :label="__('Password')" required autocomplete="new-password" />
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="password_confirmation" type="password" :label="__('Confirm Password')" required autocomplete="new-password" />

                <div>
                    <flux:label class="mb-2 block">{{ __('Roles') }}</flux:label>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($roles as $role)
                            <label class="inline-flex items-center gap-2">
                                <flux:checkbox name="roles[]" :value="$role->name" :checked="in_array($role->name, old('roles', []))" />
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Create User') }}</flux:button>
                    <flux:button variant="ghost" :href="route('users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
