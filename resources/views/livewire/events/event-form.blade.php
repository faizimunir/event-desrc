<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:input wire:model="title" type="text" :label="__('Title')" required autofocus />
        @error('title')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <div>
            <flux:label class="mb-2 block">{{ __('Status') }}</flux:label>
            <flux:select wire:model="status" class="w-full" required>
                <flux:select.option value="draft">{{ __('Draft') }} — {{ __('default, not visible on main page') }}</flux:select.option>
                <flux:select.option value="published">{{ __('Published') }} — {{ __('visible on main page, registration not open') }}</flux:select.option>
                <flux:select.option value="open_regist">{{ __('Open Regist') }} — {{ __('registration open') }}</flux:select.option>
                <flux:select.option value="closed_regist">{{ __('Closed Regist') }} — {{ __('registration closed') }}</flux:select.option>
                <flux:select.option value="live">{{ __('Live') }} — {{ __('event in progress') }}</flux:select.option>
                <flux:select.option value="done">{{ __('Done') }} — {{ __('event finished') }}</flux:select.option>
            </flux:select>
            @error('status')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <flux:radio.group wire:model="category" :label="__('Category')" variant="cards" class="max-sm:flex-col">
            <flux:radio value="umur" :label="__('Umur')" />
            <flux:radio value="tahun" :label="__('Tahun')" />
        </flux:radio.group>
        @error('category')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        @canAs('manage_live_results')
            <flux:field variant="inline">
                <flux:label class="mb-0">{{ __('Live Result') }}</flux:label>
                <flux:switch wire:model="has_live_result" />
            </flux:field>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Jika dinonaktifkan, event ini tidak akan muncul di halaman Live Result publik.') }}
            </p>
            @error('has_live_result')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        @endcanAs

        @if ($event)
            <flux:field variant="inline">
                <flux:label class="mb-0">{{ __('Show participants to the public') }}</flux:label>
                <flux:switch wire:model="show_participants_publicly" />
            </flux:field>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('When enabled, visitors can see the participant list on the public event page.') }}
            </p>
            @error('show_participants_publicly')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        @endif

        <div class="grid grid-cols-2 gap-4">
        <flux:input wire:model="start_at" type="datetime-local" :label="__('Start')" required />
        @error('start_at')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="end_at" type="datetime-local" :label="__('End')" />
        @error('end_at')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Location') }}</flux:label>
            <flux:select wire:model="location_id" :placeholder="__('Select location')" class="w-full">
                <flux:select.option value="">{{ __('— No location —') }}</flux:select.option>
                @foreach ($locations as $loc)
                    <flux:select.option :value="$loc->id">{{ $loc->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('location_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Description') }}</flux:label>
            <flux:textarea wire:model="description" rows="4"></flux:textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Organizer') }}</flux:label>
            <flux:select wire:model="organizer_id" :placeholder="__('— Select —')" class="w-full">
                <flux:select.option value="">{{ __('— No organizer —') }}</flux:select.option>
                @foreach ($organizers as $org)
                    <flux:select.option :value="$org->id">{{ $org->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('organizer_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Racing committee') }}</flux:label>
            <flux:select wire:model="racing_committee_id" :placeholder="__('— Select —')" class="w-full">
                <flux:select.option value="">{{ __('— No racing committee —') }}</flux:select.option>
                @foreach ($racingCommittees as $rc)
                    <flux:select.option :value="$rc->id">{{ $rc->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('racing_committee_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Master of ceremony') }}</flux:label>
            <flux:select wire:model="master_of_ceremony_id" :placeholder="__('— Select —')" class="w-full">
                <flux:select.option value="">{{ __('— No master of ceremony —') }}</flux:select.option>
                @foreach ($masterOfCeremonies as $moc)
                    <flux:select.option :value="$moc->id">{{ $moc->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('master_of_ceremony_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
        <flux:input wire:model="registration_opens_at" type="datetime-local" :label="__('Registration opens at')" />
        @error('registration_opens_at')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <flux:input wire:model="registration_closes_at" type="datetime-local" :label="__('Registration closes at')" />
        @error('registration_closes_at')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Payment methods') }}</flux:label>
            <div class="space-y-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model.live="payment_methods" value="{{ \App\Models\Event::PAYMENT_MANUAL }}" class="rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
                    <span>{{ __('Manual bank transfer (upload proof)') }}</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model.live="payment_methods" value="{{ \App\Models\Event::PAYMENT_QRIS }}" class="rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
                    <span>{{ __('QRIS / automatic (Moota)') }}</span>
                </label>
            </div>
            @error('payment_methods')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            @error('payment_methods.*')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @if (in_array(\App\Models\Event::PAYMENT_MANUAL, $payment_methods ?? [], true))
            <div>
                <flux:label class="mb-2 block">{{ __('Bank accounts for manual transfer') }}</flux:label>
                <p class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Select which accounts participants can use. If you choose more than one, they pick an account when paying.') }}
                </p>
                @if ($accounts->isEmpty())
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('No bank accounts exist yet. Create accounts first, then assign them here.') }}</p>
                @else
                    <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-600">
                        @foreach ($accounts as $acc)
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="account_ids" value="{{ $acc->id }}" class="rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
                                <span>{{ $acc->acc_name }} — {{ $acc->acc_bank }} ({{ $acc->acc_number }})</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('account_ids')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @error('account_ids.*')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div>
            <flux:label class="mb-2 block">{{ __('Poster') }}</flux:label>
            <flux:file-upload wire:model="poster" label="{{ __('Upload poster') }}">
                <flux:file-upload.dropzone
                    heading="{{ __('Drop file or click to browse') }}"
                    text="{{ __('JPG, PNG, GIF up to 10MB') }}"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('poster')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($poster)
                    <flux:file-item
                        :heading="$poster->getClientOriginalName()"
                        :image="$poster->temporaryUrl()"
                        :size="$poster->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="removePoster" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($event?->posterUrl())
                    <flux:file-item
                        heading="{{ __('Current poster') }}"
                        :image="$event->posterUrl()"
                    >
                        <x-slot name="actions">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Replace by uploading above') }}</span>
                        </x-slot>
                    </flux:file-item>
                @endif
            </div>
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Logo (for Live Result)') }}</flux:label>
            <flux:file-upload wire:model="logo" :label="__('Upload logo')">
                <flux:file-upload.dropzone
                    heading="{{ __('Drop file or click to browse') }}"
                    text="{{ __('JPG, PNG, GIF up to 5MB') }}"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('logo')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($logo)
                    <flux:file-item
                        :heading="$logo->getClientOriginalName()"
                        :image="$logo->temporaryUrl()"
                        :size="$logo->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="removeLogo" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($event?->logoUrl())
                    <flux:file-item
                        heading="{{ __('Current logo') }}"
                        :image="$event->logoUrl()"
                    >
                        <x-slot name="actions">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Replace by uploading above') }}</span>
                        </x-slot>
                    </flux:file-item>
                @endif
            </div>
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Jersey sizes') }}</flux:label>
            <flux:textarea wire:model="jersey_sizes" rows="2" placeholder="S, M, L, XL"></flux:textarea>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Pisahkan ukuran dengan koma atau baris baru. Contoh: XS, S, M, L, XL, 2XL') }}
            </p>
            @error('jersey_sizes')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:label class="mb-2 block">{{ __('Size chart') }}</flux:label>
            <flux:file-upload wire:model="sizeChart" :label="__('Upload size chart')">
                <flux:file-upload.dropzone
                    heading="{{ __('Drop file or click to browse') }}"
                    text="{{ __('JPG, PNG, GIF up to 10MB') }}"
                    with-progress
                    inline
                />
            </flux:file-upload>
            @error('sizeChart')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex flex-col gap-2">
                @if ($sizeChart)
                    <flux:file-item
                        :heading="$sizeChart->getClientOriginalName()"
                        :image="$sizeChart->temporaryUrl()"
                        :size="$sizeChart->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="removeSizeChart" />
                        </x-slot>
                    </flux:file-item>
                @elseif ($event?->sizeChartUrl())
                    <flux:file-item
                        :heading="__('Current size chart')"
                        :image="$event->sizeChartUrl()"
                    >
                        <x-slot name="actions">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Replace by uploading above') }}</span>
                        </x-slot>
                    </flux:file-item>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" type="submit">{{ $event ? __('Update Event') : __('Create Event') }}</flux:button>
            <flux:button variant="ghost" :href="route('events.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
    @if ($event)
        @canAs('event.delete')
            @can('delete', $event)
                <form id="delete-event-form-{{ $event->id }}" method="post" action="{{ route('events.destroy', $event) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this event?')) }}')) document.getElementById('delete-event-form-{{ $event->id }}').submit()"
                    >
                        {{ __('Delete Event') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    @endif
</div>
