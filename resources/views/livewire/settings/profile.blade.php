<div class="flex h-full w-full flex-1 flex-col">
    <x-admin-hero-header
        :heading="__('Profile')"
        :subheading="__('Settings')"
    />

    <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
        <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

        <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
            <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                <div>
                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                    @if ($this->hasUnverifiedEmail)
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                    </div>

                    <x-action-message class="me-3" on="profile-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>

            @if ($this->showDeleteUser)
                <livewire:settings.delete-user-form />
            @endif
        </x-settings.layout>
    </div>
</div>
