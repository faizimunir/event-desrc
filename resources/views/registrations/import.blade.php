@php
    $result = session('import_result');
    $registrationsUrl = route('events.show', [$event, 'tab' => 'registrations']);
    $deadlineLabel = fn (int $minutes) => match (true) {
        $minutes >= 1440 && $minutes % 1440 === 0 => trans_choice(':count day|:count days', intdiv($minutes, 1440), ['count' => intdiv($minutes, 1440)]),
        $minutes >= 60 && $minutes % 60 === 0 => trans_choice(':count hour|:count hours', intdiv($minutes, 60), ['count' => intdiv($minutes, 60)]),
        default => trans_choice(':count minute|:count minutes', $minutes, ['count' => $minutes]),
    };
@endphp

<x-layouts::app :title="__('Import registrations') . ' — ' . $event->title" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        @include('registrations.partials.import-header', ['event' => $event, 'title' => __('Import registrations'), 'backUrl' => $registrationsUrl])

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            @if ($result)
                @php
                    $notifyFailed = collect($result['imported'])->where('notified', false)->count();
                @endphp
                <div class="max-w-3xl space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <flux:heading size="lg">{{ __('Import result') }}</flux:heading>
                    <ul class="space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                        <li>{{ __(':count saved', ['count' => count($result['imported'])]) }}</li>
                        <li>{{ __(':count skipped because of errors', ['count' => $result['skipped']]) }}</li>
                        <li>{{ __(':count failed while saving', ['count' => count($result['failed'])]) }}</li>
                    </ul>

                    @if ($notifyFailed > 0)
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            {{ __(':count registration(s) were saved but their notification could not be sent. Open the registration to resend it.', ['count' => $notifyFailed]) }}
                        </flux:callout>
                    @endif

                    @if ($result['failed'] !== [])
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase text-zinc-500">
                                    <tr>
                                        <th class="py-1 pr-3">{{ __('Row') }}</th>
                                        <th class="py-1 pr-3">{{ __('Rider') }}</th>
                                        <th class="py-1">{{ __('Reason') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                    @foreach ($result['failed'] as $failure)
                                        <tr>
                                            <td class="py-1 pr-3">{{ $failure['row'] }}</td>
                                            <td class="py-1 pr-3">{{ $failure['rider'] }}</td>
                                            <td class="py-1 text-red-600 dark:text-red-400">{{ $failure['reason'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <flux:button variant="primary" :href="$registrationsUrl" wire:navigate icon="arrow-right">
                        {{ __('View registrations') }}
                    </flux:button>
                </div>
            @endif

            <div class="max-w-3xl space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('1. Download the template') }}</flux:heading>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('The Excel template lists this event\'s brackets, packages and jersey sizes as dropdowns. One row = one rider in one bracket (max :max rows per file).', ['max' => $maxRows]) }}
                    </p>
                </div>
                <flux:button :href="route('events.registrations.import.template', $event)" icon="arrow-down-tray">
                    {{ __('Download template (.xlsx)') }}
                </flux:button>
            </div>

            <form
                action="{{ route('events.registrations.import.preview', $event) }}"
                method="post"
                enctype="multipart/form-data"
                class="max-w-3xl space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800/50"
                x-data="{ mode: @js(old('mode', 'paid')) }"
            >
                @csrf

                <flux:heading size="lg">{{ __('2. Upload and choose how to save') }}</flux:heading>

                <div>
                    <flux:label for="file" class="mb-1">{{ __('File (.xlsx or .csv)') }}</flux:label>
                    <input
                        id="file"
                        type="file"
                        name="file"
                        accept=".xlsx,.csv"
                        required
                        class="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-orange-700 hover:file:bg-orange-100 dark:text-zinc-300"
                    >
                    @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset class="space-y-2">
                    <legend class="mb-1 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('Save registrations as') }}</legend>

                    <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" :class="mode === 'paid' ? 'border-orange-400 bg-orange-50/60 dark:bg-orange-500/10' : ''">
                        <input type="radio" name="mode" value="paid" x-model="mode" class="mt-1">
                        <span>
                            <span class="block text-sm font-medium">{{ __('Already paid (approved)') }}</span>
                            <span class="block text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Use when the admin or the community already collected payment outside the system. Registrations are approved, payments are marked successful and e-tickets are issued and sent.') }}
                            </span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" :class="mode === 'unpaid' ? 'border-orange-400 bg-orange-50/60 dark:bg-orange-500/10' : ''">
                        <input type="radio" name="mode" value="unpaid" x-model="mode" class="mt-1">
                        <span>
                            <span class="block text-sm font-medium">{{ __('Waiting for payment') }}</span>
                            <span class="block text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Registrations stay pending and hold their quota. Each parent receives the standard payment link and pays through the normal flow.') }}
                            </span>
                        </span>
                    </label>
                    @error('mode')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div x-show="mode === 'unpaid'" x-cloak>
                    <flux:label for="deadline_minutes" class="mb-1">{{ __('Payment link valid for') }}</flux:label>
                    <flux:select id="deadline_minutes" name="deadline_minutes">
                        @foreach ($deadlineOptions as $minutes)
                            <option value="{{ $minutes }}" @selected((int) old('deadline_minutes', $defaultDeadline) === $minutes)>{{ $deadlineLabel($minutes) }}</option>
                        @endforeach
                    </flux:select>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Unpaid registrations are cancelled automatically after this period and their quota is released.') }}
                    </p>
                    @error('deadline_minutes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Nothing is saved yet: you will review a validation preview first. Notifications are sent only after the registrations have been saved.') }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <flux:button variant="primary" type="submit" icon="arrow-up-tray">{{ __('Upload and preview') }}</flux:button>
                    <flux:button variant="ghost" :href="$registrationsUrl" wire:navigate type="button">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
