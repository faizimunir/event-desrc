<x-layouts::app :title="__('Add registration') . ' — ' . $event->title">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'registrations'])" wire:navigate icon="arrow-left">
                {{ __('Back to event') }}
            </flux:button>
        </div>

        <flux:heading>{{ __('Add registration') }} — {{ $event->title }}</flux:heading>

        @if ($errors->isNotEmpty())
            <flux:callout variant="danger" class="rounded-lg">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        <div class="max-w-xl rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
            <form action="{{ route('events.registrations.store', $event) }}" method="post" class="p-6 space-y-4">
                @csrf

                <div>
                    <flux:label for="rider_id" class="mb-1">{{ __('Rider') }}</flux:label>
                    <flux:select id="rider_id" name="rider_id" :placeholder="__('Select rider')" required>
                        <option value="">{{ __('— Select rider —') }}</option>
                        @foreach ($riders as $r)
                            <option value="{{ $r->id }}" @selected(old('rider_id') == $r->id)>
                                {{ $r->name }}@if ($r->nickname) ({{ $r->nickname }})@endif
                                @if ($r->user?->whatsapp) — {{ $r->user->whatsapp }}@endif
                            </option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label for="bracket_id" class="mb-1">{{ __('Bracket') }}</flux:label>
                    <flux:select id="bracket_id" name="bracket_id" :placeholder="__('Select bracket')" required>
                        <option value="">{{ __('— Select bracket —') }}</option>
                        @foreach ($event->brackets as $b)
                            <option value="{{ $b->id }}" @selected(old('bracket_id') == $b->id)>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label for="package_id" class="mb-1">{{ __('Package') }}</flux:label>
                    <flux:select id="package_id" name="package_id" :placeholder="__('Select package')" required>
                        <option value="">{{ __('— Select package —') }}</option>
                        @foreach ($event->packages as $pkg)
                            <option value="{{ $pkg->id }}" @selected(old('package_id') == $pkg->id)>
                                {{ $pkg->name }} — {{ $pkg->formatted_payable_amount }}
                            </option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($event->packages->contains(fn ($pkg) => $pkg->hasJerseyReward()))
                    <div>
                        <flux:label for="jersey_size" class="mb-1">{{ __('Jersey size') }}</flux:label>
                        <flux:select id="jersey_size" name="jersey_size" :placeholder="__('Select size')">
                            <option value="">{{ __('— Select size —') }}</option>
                            @foreach ($event->jerseySizeOptions() as $size)
                                <option value="{{ $size }}" @selected(old('jersey_size') === $size)>{{ $size }}</option>
                            @endforeach
                        </flux:select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Required when the selected package includes a jersey.') }}
                        </p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 pt-2">
                    <flux:button variant="primary" type="submit" icon="plus">
                        {{ __('Add registration') }}
                    </flux:button>
                    <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'registrations'])" wire:navigate type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
