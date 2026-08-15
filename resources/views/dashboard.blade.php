<x-layouts::app :title="__('Dashboard')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-hero-header :heading="__('Dashboard')" />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            {{-- Demo: permission checks use active role. Switch role to see buttons appear/disappear. --}}
            @if(auth()->user()->hasMultipleRoles())
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Active role') }}: <strong>{{ auth()->user()->activeRole()?->name }}</strong>
                    </p>
                    <div class="flex gap-2">
                        @canAs('rider.update')
                            <flux:badge color="green">{{ __('Can edit rider') }}</flux:badge>
                        @else
                            <flux:badge color="zinc">{{ __('Cannot edit rider') }}</flux:badge>
                        @endcanAs
                        @canAs('event.create')
                            <flux:badge color="green">{{ __('Can create event') }}</flux:badge>
                        @else
                            <flux:badge color="zinc">{{ __('Cannot create event') }}</flux:badge>
                        @endcanAs
                    </div>
                </div>
            @endif

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
            <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
    </div>
</x-layouts::app>
