@php
    $uploadUrl = route('events.registrations.import.create', $event);
    $isPaid = $mode === 'paid';
    $hasErrors = $report['invalid'] > 0;
    $canImport = $report['valid'] > 0;
    $deadlineLabel = match (true) {
        $deadlineMinutes >= 1440 && $deadlineMinutes % 1440 === 0 => trans_choice(':count day|:count days', intdiv($deadlineMinutes, 1440), ['count' => intdiv($deadlineMinutes, 1440)]),
        $deadlineMinutes >= 60 && $deadlineMinutes % 60 === 0 => trans_choice(':count hour|:count hours', intdiv($deadlineMinutes, 60), ['count' => intdiv($deadlineMinutes, 60)]),
        default => trans_choice(':count minute|:count minutes', $deadlineMinutes, ['count' => $deadlineMinutes]),
    };
@endphp

<x-layouts::app :title="__('Import preview') . ' — ' . $event->title" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        @include('registrations.partials.import-header', ['event' => $event, 'title' => __('Import preview'), 'backUrl' => $uploadUrl])

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <div class="grid max-w-3xl grid-cols-3 gap-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <p class="text-xs uppercase text-zinc-500">{{ __('Rows') }}</p>
                    <p class="text-2xl font-semibold">{{ count($report['rows']) }}</p>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                    <p class="text-xs uppercase text-green-700 dark:text-green-400">{{ __('Ready to import') }}</p>
                    <p class="text-2xl font-semibold text-green-700 dark:text-green-400">{{ $report['valid'] }}</p>
                </div>
                <div class="rounded-xl border p-4 {{ $hasErrors ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50' }}">
                    <p class="text-xs uppercase {{ $hasErrors ? 'text-red-700 dark:text-red-400' : 'text-zinc-500' }}">{{ __('With errors') }}</p>
                    <p class="text-2xl font-semibold {{ $hasErrors ? 'text-red-700 dark:text-red-400' : '' }}">{{ $report['invalid'] }}</p>
                </div>
            </div>

            <div class="max-w-3xl space-y-2 rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                <p class="font-medium">{{ $isPaid ? __('Mode: already paid (approved)') : __('Mode: waiting for payment') }}</p>
                <p class="text-zinc-600 dark:text-zinc-400">
                    @if ($isPaid)
                        {{ __('After a registration is saved, an e-ticket is issued and sent to the parent by WhatsApp and email (standard e-ticket message).') }}
                    @else
                        {{ __('After a registration is saved, the parent receives the standard payment link by WhatsApp and email. The link stays valid for :period.', ['period' => $deadlineLabel]) }}
                    @endif
                </p>
                @if ($report['new_teams'] !== [])
                    <p class="text-zinc-600 dark:text-zinc-400">
                        {{ __('New teams that will be created:') }}
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ implode(', ', $report['new_teams']) }}</span>
                    </p>
                @endif
            </div>

            <div class="max-w-full overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                        <tr>
                            <th class="px-3 py-2">{{ __('Row') }}</th>
                            <th class="px-3 py-2">{{ __('Rider') }}</th>
                            <th class="px-3 py-2">{{ __('WhatsApp') }}</th>
                            <th class="px-3 py-2">{{ __('Bracket') }}</th>
                            <th class="px-3 py-2">{{ __('Package') }}</th>
                            <th class="px-3 py-2">{{ __('Check') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($report['rows'] as $row)
                            <tr class="{{ $row['errors'] !== [] ? 'bg-red-50/60 dark:bg-red-950/20' : '' }}">
                                <td class="px-3 py-2 align-top">{{ $row['row'] }}</td>
                                <td class="px-3 py-2 align-top">{{ $row['data']['rider_name'] ?? '—' }}</td>
                                <td class="px-3 py-2 align-top">{{ $row['data']['whatsapp'] ?? '—' }}</td>
                                <td class="px-3 py-2 align-top">{{ $row['data']['bracket'] ?? '—' }}</td>
                                <td class="px-3 py-2 align-top">{{ $row['data']['package'] ?? '—' }}</td>
                                <td class="px-3 py-2 align-top">
                                    @if ($row['errors'] === [] && $row['warnings'] === [])
                                        <span class="text-green-600 dark:text-green-400">{{ __('OK') }}</span>
                                    @endif
                                    @foreach ($row['errors'] as $error)
                                        <p class="text-red-600 dark:text-red-400">{{ $error }}</p>
                                    @endforeach
                                    @foreach ($row['warnings'] as $warning)
                                        <p class="text-amber-600 dark:text-amber-400">{{ $warning }}</p>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <form
                action="{{ route('events.registrations.import.store', [$event, $token]) }}"
                method="post"
                class="max-w-3xl space-y-3"
                x-data="{ skip: false, submitting: false }"
                x-on:submit="submitting = true"
            >
                @csrf

                @if ($hasErrors && $canImport)
                    <label class="flex cursor-pointer items-start gap-2 text-sm">
                        <input type="checkbox" name="skip_invalid" value="1" x-model="skip" class="mt-1">
                        <span>{{ __('Import only the :valid valid rows and skip the :invalid rows with errors.', ['valid' => $report['valid'], 'invalid' => $report['invalid']]) }}</span>
                    </label>
                @elseif ($hasErrors)
                    <flux:callout variant="danger" icon="x-circle">
                        {{ __('No row can be imported. Fix the errors in your file and upload it again.') }}
                    </flux:callout>
                @endif

                <div class="flex flex-wrap gap-2">
                    @if ($canImport)
                        <flux:button
                            variant="primary"
                            type="submit"
                            icon="check"
                            x-bind:disabled="submitting || ({{ $hasErrors ? 'true' : 'false' }} && ! skip)"
                        >
                            {{ __('Import :count registrations', ['count' => $hasErrors ? $report['valid'] : count($report['rows'])]) }}
                        </flux:button>
                    @endif
                    <flux:button variant="ghost" :href="$uploadUrl" wire:navigate type="button">
                        {{ $canImport ? __('Cancel') : __('Upload another file') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
