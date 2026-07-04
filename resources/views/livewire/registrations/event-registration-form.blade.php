<div class="space-y-5"
    x-on:registration-bracket-selected.window="$wire.set('bracket_id', $event.detail.id)"
    x-on:registration-package-selected.window="$wire.set('package_id', $event.detail.id)">
    @if ($showSimilarChoice && count($similarRiders) > 0)
        <flux:modal name="duplicate-rider-modal" focusable class="max-w-2xl" dismissible>
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('You may already be registered') }}</flux:heading>
                    <flux:subheading class="mt-1">
                        {{ __('We found a rider with similar data for this WhatsApp number. Compare below and use the existing profile to continue.') }}
                    </flux:subheading>
                </div>
                <ul class="space-y-4">
                    @foreach ($similarRiders as $sr)
                        <li class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-sm font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                    {{ __('Similarity') }}: {{ $sr['score'] }}%
                                </span>
                                <flux:button type="button" variant="primary" size="sm"
                                    wire:click="submitConfirm({{ $sr['id'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="submitConfirm">
                                    {{ __('Use this profile') }}
                                </flux:button>
                            </div>
                            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-3 dark:border-blue-800 dark:bg-blue-900/10">
                                    <div class="mb-2 font-medium text-blue-800 dark:text-blue-200">{{ __('New data (from form)') }}</div>
                                    <dl class="space-y-1.5 text-zinc-700 dark:text-zinc-300">
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Full name') }}:</span> {{ $name ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Nickname') }}:</span> {{ $nickname ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Place of birth') }}:</span> {{ $pob ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Date of birth') }}:</span> {{ $dob ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}:</span> {{ match ($gender) { 'boys' => __('Boys'), 'girls' => __('Girls'), 'other' => __('Other'), default => $gender ?: '—' } }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}:</span> {{ $number_plate ?: '—' }}</div>
                                    </dl>
                                </div>
                                <div class="rounded-lg border border-zinc-200 bg-zinc-100/80 p-3 dark:border-zinc-600 dark:bg-zinc-700/30">
                                    <div class="mb-2 font-medium text-zinc-800 dark:text-zinc-200">{{ __('Existing profile') }}</div>
                                    <dl class="space-y-1.5 text-zinc-700 dark:text-zinc-300">
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Full name') }}:</span> {{ $sr['name'] ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Nickname') }}:</span> {{ $sr['nickname'] ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Place of birth') }}:</span> {{ $sr['pob'] ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Date of birth') }}:</span> {{ $sr['dob'] ?: '—' }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}:</span> {{ $sr['gender_label'] ?? ($sr['gender'] ?? '—') }}</div>
                                        <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}:</span> {{ $sr['number_plate'] ?: '—' }}</div>
                                    </dl>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <flux:button variant="ghost" size="sm" wire:click="chooseNewRider">{{ __('Register as new rider') }}</flux:button>
            </div>
        </flux:modal>
    @else
        <form wire:submit="submit" class="space-y-5">
            <div class="registration-summary">
                <span class="registration-summary-chip">
                    <flux:icon name="squares-2x2" variant="mini" class="size-4 text-orange-500" />
                    <span>{{ $this->selectedBracketLabel }}</span>
                </span>
                @if ($event->packages->isNotEmpty())
                    <span class="registration-summary-chip">
                        <flux:icon name="gift" variant="mini" class="size-4 text-orange-500" />
                        <span>{{ $this->selectedPackageLabel }}</span>
                    </span>
                @endif
            </div>

            <div class="registration-form-section !border-t-0 !pt-0">
                <p class="registration-form-block-title">
                    <flux:icon name="user" class="size-5 text-orange-500" />
                    {{ __('Parent / Guardian') }}
                </p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="parent_name" type="text" :label="__('Parent / Guardian name')" required />
                    <flux:input wire:model="whatsapp" type="tel" :label="__('WhatsApp number')" :placeholder="__('e.g. 08123456789')" required />
                </div>
            </div>

            <div class="registration-form-section">
                <p class="registration-form-block-title">
                    <flux:icon name="identification" class="size-5 text-orange-500" />
                    {{ __('Rider data') }}
                </p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="name" type="text" :label="__('Full name')" required />
                    <flux:input wire:model="nickname" type="text" :label="__('Nickname')" required />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="pob" type="text" :label="__('Place of birth')" required />
                    <flux:input wire:model="dob" type="date" :label="__('Date of birth')" required />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:select wire:model="gender" :placeholder="__('— Select —')" :label="__('Gender')" required>
                        <flux:select.option value="boys">{{ __('Boys') }}</flux:select.option>
                        <flux:select.option value="girls">{{ __('Girls') }}</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="number_plate" type="text" :label="__('Number plate')" required maxlength="3" inputmode="numeric" pattern="[0-9]{1,3}" />
                </div>

                @if ($this->requiresJerseySize)
                    <div class="space-y-2" x-data="{ sizeChartPreviewOpen: false }" @keydown.escape.window="sizeChartPreviewOpen = false">
                        <flux:select wire:model="jersey_size" :placeholder="__('— Select size —')" :label="__('Jersey size')" required>
                            @foreach ($this->jerseySizeOptions as $size)
                                <flux:select.option :value="$size">{{ $size }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @if ($event->sizeChartUrl())
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                <button type="button" @click="sizeChartPreviewOpen = true"
                                    class="underline hover:text-zinc-700 dark:hover:text-zinc-300">
                                    {{ __('View size chart') }}
                                </button>
                            </p>
                            <div x-show="sizeChartPreviewOpen" x-transition.opacity x-cloak
                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                @click.self="sizeChartPreviewOpen = false" role="dialog" aria-modal="true"
                                :aria-hidden="!sizeChartPreviewOpen">
                                <img src="{{ $event->sizeChartUrl() }}" alt="{{ __('Size chart') }}"
                                    class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-xl" @click.stop />
                            </div>
                        @endif
                    </div>
                @endif

            <div id="team-pillbox-wrapper">
                <flux:label class="mb-2 block">{{ __('Community / Team') }} <span class="text-red-500">*</span></flux:label>

                {{-- Dummy required input to trigger browser-style "fill the field" when selectedTeamIds is empty --}}
                <div id="team-pillbox-control" class="relative">
                    <input
                        id="native-fill-team"
                        type="text"
                        required
                        value="{{ count($selectedTeamIds) > 0 ? '1' : '' }}"
                        aria-hidden="true"
                        tabindex="-1"
                        style="position:absolute; inset:0; opacity:0; width:100%; height:100%; pointer-events:none;"
                    />

                    <flux:pillbox wire:model.live="selectedTeamIds" variant="combobox" multiple :filter="false"
                        placeholder="{{ __('Search or add team...') }}">
                        <x-slot name="input">
                            <flux:pillbox.input wire:model.live="teamSearch" placeholder="{{ __('Search or add team...') }}" />
                        </x-slot>
                        @foreach ($this->teams as $team)
                            <flux:pillbox.option :value="$team->id">{{ $team->name }}</flux:pillbox.option>
                        @endforeach
                        <flux:pillbox.option.create wire:click="createTeam" min-length="1">
                            {{ __('Create') }} "<span wire:text="teamSearch"></span>"
                        </flux:pillbox.option.create>
                        <x-slot name="empty">
                            <flux:pillbox.option.empty when-loading="{{ __('Loading...') }}">
                                {{ __('No team found. Type to search or create.') }}
                            </flux:pillbox.option.empty>
                        </x-slot>
                    </flux:pillbox>
                </div>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Cari team dari daftar atau tulis nama team baru lalu klik (+ Create)') }}</p>
            </div>

            <div id="photo-kia-wrapper">
                {{-- Dummy required input to trigger browser-style "fill the field" when photo_kia is empty --}}
                <div id="photo-kia-control" class="relative">
                    <input
                        id="native-fill-photo"
                        type="text"
                        required
                        value="{{ $photo_kia ? '1' : '' }}"
                        aria-hidden="true"
                        tabindex="-1"
                        style="position:absolute; inset:0; opacity:0; width:100%; height:100%; pointer-events:none;"
                    />

                    <flux:file-upload wire:model="photo_kia" :label="__('Photo KIA (Kartu Identitas Anak)') . ' *'">
                        <flux:file-upload.dropzone
                            :heading="__('Drop file here or click to browse')"
                            :text="__('JPG, PNG, WebP up to :max KB', ['max' => config('media.max_upload_size_kb', 2048)])"
                            with-progress
                            inline
                        />
                    </flux:file-upload>
                </div>
                <div class="mt-3 flex flex-col gap-2">
                    @if ($photo_kia)
                        <flux:file-item
                            :heading="$photo_kia->getClientOriginalName()"
                            :image="$photo_kia->temporaryUrl()"
                            :size="$photo_kia->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removePhotoKia" />
                            </x-slot>
                        </flux:file-item>
                    @endif
                </div>
            </div>
            </div>

            <flux:button type="submit" variant="primary" icon="paper-airplane"
                class="w-full justify-center !bg-orange-500 hover:!bg-orange-400 sm:w-auto"
                wire:loading.attr="disabled"
                wire:target="submit,photo_kia">
                <span wire:loading.remove wire:target="submit">{{ __('Submit registration') }}</span>
                <span wire:loading wire:target="submit">{{ __('Submitting...') }}</span>
            </flux:button>
        </form>
    @endif

    @push('scripts')
        <script>
            (() => {
                if (window.__registrationNativeValidationInstalled) return;
                window.__registrationNativeValidationInstalled = true;

                window.addEventListener('registration-native-validation', (event) => {
                    const field = event?.detail?.field;
                    const inputId = field === 'team' ? 'native-fill-team' : 'native-fill-photo';
                    const input = document.getElementById(inputId);
                    if (!input) return;

                    // Show native "fill the field" message.
                    input.reportValidity();

                    // Move focus to the visible control (search input / file input) inside the wrappers.
                    if (field === 'team') {
                        const focusTarget = document.querySelector('#team-pillbox-control input:not(#native-fill-team)');
                        focusTarget?.focus?.();
                    } else {
                        const fileInput = document.querySelector('#photo-kia-wrapper input[type="file"]');
                        if (fileInput) fileInput.focus();
                        else {
                            const focusTarget = document.querySelector('#photo-kia-wrapper button, #photo-kia-wrapper input:not(#native-fill-photo)');
                            focusTarget?.focus?.();
                        }
                    }
                });
            })();
        </script>
    @endpush
</div>
