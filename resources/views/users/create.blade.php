<x-layouts::app :title="__('Add User')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('users.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Add User') }}</flux:heading>

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

            <flux:input name="phone" type="text" :label="__('Phone (WA)')" :value="old('phone')" />
            @error('phone')
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
</x-layouts::app>
