<x-layouts::app :title="__('My Rider')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-hero-header
            :heading="__('My Rider')"
            create-permission="myrider.manage"
            :create-href="route('my-rider.create')"
            :create-label="__('Add Rider')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            @if ($riders->isEmpty())
                <div class="users-list-panel px-4 py-12 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="user" class="size-5 text-zinc-400" />
                    </div>
                    <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No rider profile yet.') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
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
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Your riders') }}</h2>
                    <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                        {{ number_format($riders->count()) }}
                    </span>
                </div>

                <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($riders as $rider)
                        <li class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                            <div class="flex items-start gap-4 p-5">
                                <div class="relative size-20 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700">
                                    @if ($rider->getFirstMediaUrl('photo_rider'))
                                        <img
                                            src="{{ $rider->getFirstMediaUrl('photo_rider', 200) }}"
                                            alt=""
                                            class="size-full object-cover"
                                        />
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
    </div>
</x-layouts::app>
