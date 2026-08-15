<div class="flex h-full w-full flex-1 flex-col">
    <x-admin-hero-header
        :heading="__('Appearance')"
        :subheading="__('Settings')"
    />

    <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
        <flux:heading class="sr-only">{{ __('Appearance Settings') }}</flux:heading>

        <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </x-settings.layout>
    </div>
</div>
