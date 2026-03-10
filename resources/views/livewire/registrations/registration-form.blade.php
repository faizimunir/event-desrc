<div class="space-y-6">
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($showSimilarChoice && count($similarRiders) > 0)
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 space-y-3">
            <p class="font-medium text-amber-800 dark:text-amber-200">{{ __('You may already be registered') }}</p>
            <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('We found a rider with the same name, date of birth and gender for this WhatsApp number. Use this profile or register as a new rider.') }}</p>
            <ul class="space-y-2">
                @foreach ($similarRiders as $sr)
                    <li class="flex items-center justify-between gap-2 text-sm">
                        <span>{{ $sr['name'] }} — {{ $sr['dob'] }} ({{ $sr['gender_label'] ?? $sr['gender'] }})</span>
                        <flux:button variant="primary" size="sm" wire:click="submitConfirm({{ $sr['id'] }})">{{ __('Use this profile') }}</flux:button>
                    </li>
                @endforeach
            </ul>
            <flux:button variant="ghost" size="sm" wire:click="chooseNewRider">{{ __('Register as new rider') }}</flux:button>
        </div>
    @else
    <form wire:submit="submit">
        <div class="space-y-6">
            <hr class="border-zinc-200 dark:border-zinc-700" />
            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Parent / Guardian') }}</h2>
            <flux:input wire:model="parent_name" type="text" :label="__('Parent / Guardian name')" required />
            <flux:input wire:model="whatsapp" type="tel" :label="__('WhatsApp number')" :placeholder="__('e.g. 08123456789')" required />

            @if ($event->packages->count() > 1)
            <div>
                <flux:label class="mb-2 block">{{ __('Package') }} <span class="text-red-500">*</span></flux:label>
                <p class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Package determines registration price and race pack.') }}</p>
                <select wire:model.live="package_id" required class="w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ __('— Select package —') }}</option>
                    @foreach ($event->packages as $pkg)
                        <option value="{{ $pkg->id }}">
                            {{ $pkg->name }} — {{ $pkg->formatted_price }}
                            @if ($pkg->race_pack)
                                ({{ \Illuminate\Support\Str::limit($pkg->race_pack, 40) }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('package_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div>
                <flux:label class="mb-2 block">{{ __('Bracket') }} <span class="text-red-500">*</span></flux:label>
                <select wire:model="bracket_id" required class="w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ __('— Select bracket —') }}</option>
                    @foreach ($event->brackets as $b)
                        @php $remaining = $b->remainingQuota(); @endphp
                        <option value="{{ $b->id }}" @if ($remaining !== null && $remaining <= 0) disabled @endif>
                            {{ $b->name }}
                            @if ($remaining !== null)
                                ({{ __(':count slot(s) left', ['count' => $remaining]) }})
                            @else
                                ({{ __('Open') }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <hr class="border-zinc-200 dark:border-zinc-700" />
            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Rider data') }}</h2>

            <flux:input wire:model="name" type="text" :label="__('Full name')" required autofocus />
            <flux:input wire:model="nickname" type="text" :label="__('Nickname')" />
            <flux:input wire:model="pob" type="text" :label="__('Place of birth')" />
            <flux:input wire:model="dob" type="date" :label="__('Date of birth')" required />
            <div>
                <flux:label class="mb-2 block">{{ __('Gender') }}</flux:label>
                <flux:select wire:model="gender" :placeholder="__('Select gender')" required>
                    <flux:select.option value="boys">{{ __('Boys') }}</flux:select.option>
                    <flux:select.option value="girls">{{ __('Girls') }}</flux:select.option>
                    <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                </flux:select>
            </div>
            <flux:input wire:model="number_plate" type="text" :label="__('Number plate')" />

            <div>
                <flux:label class="mb-2 block">{{ __('Community / Team') }}</flux:label>
                <flux:pillbox wire:model.live="selectedOrganizerIds" variant="combobox" multiple :filter="false"
                    placeholder="{{ __('Search or add team...') }}"
                >
                    <x-slot name="input">
                        <flux:pillbox.input wire:model.live="organizerSearch" placeholder="{{ __('Search or add team...') }}" />
                    </x-slot>
                    @foreach ($this->organizers as $organizer)
                        <flux:pillbox.option :value="$organizer->id">{{ $organizer->name }}</flux:pillbox.option>
                    @endforeach
                    <flux:pillbox.option.create wire:click="createOrganizer" min-length="1">
                        {{ __('Create') }} "<span wire:text="organizerSearch"></span>"
                    </flux:pillbox.option.create>
                    <x-slot name="empty">
                        <flux:pillbox.option.empty when-loading="{{ __('Loading...') }}">
                            {{ __('No team found. Type to search or create.') }}
                        </flux:pillbox.option.empty>
                    </x-slot>
                </flux:pillbox>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('One rider can have multiple teams. Type to search; add new name if not in list.') }}</p>
            </div>

            <div class="flex gap-2 pt-2">
                <flux:button variant="primary" type="submit">{{ __('Submit registration') }}</flux:button>
                <flux:button variant="ghost" href="{{ route('events.public.show', $event->slug) }}" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </div>
    </form>
    @endif
</div>
