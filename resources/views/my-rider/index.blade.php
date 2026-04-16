<x-layouts::app :title="__('My Rider')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading>{{ __('My Rider') }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Rider profiles linked to your account.') }}
                </p>
            </div>
            <flux:button variant="primary" :href="route('my-rider.create')" wire:navigate icon="plus">
                {{ __('Add Rider') }}
            </flux:button>
        </div>

        @if ($riders->isEmpty())
            <div
                class="rounded-xl border border-zinc-200 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                <flux:icon name="user" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
                <p class="mt-4 text-zinc-700 dark:text-zinc-300">{{ __('No rider profile yet.') }}</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Add a rider profile here, or register for an event to link a rider to your account.') }}
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <flux:button :href="route('my-rider.create')" variant="primary" wire:navigate icon="plus">
                        {{ __('Add Rider') }}
                    </flux:button>
                    <flux:button :href="route('events.public.index')" variant="ghost" wire:navigate>
                        {{ __('Browse events') }}
                    </flux:button>
                </div>
            </div>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($riders as $rider)
                    <li
                        class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="flex items-start gap-4 p-5">
                            <div
                                class="relative size-20 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700">
                                @if ($rider->getFirstMediaUrl('photo_rider'))
                                    <img src="{{ $rider->getFirstMediaUrl('photo_rider', 200) }}"
                                        alt=""
                                        class="size-full object-cover" />
                                @else
                                    <div class="flex size-full items-center justify-center text-zinc-400">
                                        <flux:icon name="user" class="size-10" />
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $rider->name }}
                                </p>
                                @if ($rider->nickname)
                                    <p class="truncate text-sm text-zinc-600 dark:text-zinc-400">
                                        “{{ $rider->nickname }}”
                                    </p>
                                @endif
                                @if ($rider->number_plate)
                                    <p class="mt-2 inline-flex rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-sm text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                                        {{ $rider->number_plate }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <dl class="space-y-2 border-t border-zinc-100 px-5 py-4 text-sm dark:border-zinc-700/80">
                            @if ($rider->gender)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</dt>
                                    <dd class="text-end text-zinc-900 dark:text-zinc-100">
                                        {{ $rider->gender_label }}
                                    </dd>
                                </div>
                            @endif
                            @if ($rider->dob)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Date of birth') }}</dt>
                                    <dd class="text-end text-zinc-900 dark:text-zinc-100">
                                        {{ $rider->dob->translatedFormat('d M Y') }}
                                    </dd>
                                </div>
                            @endif
                            @if ($rider->pob)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Place of birth') }}</dt>
                                    <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $rider->pob }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-3">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Registrations') }}</dt>
                                <dd class="text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $rider->registrations_count }}
                                </dd>
                            </div>
                        </dl>
                        @if ($rider->teams->isNotEmpty())
                            <div class="border-t border-zinc-100 px-5 py-4 dark:border-zinc-700/80">
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    {{ __('Teams') }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($rider->teams as $team)
                                        <flux:badge color="zinc" size="sm">{{ $team->name }}</flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts::app>
