<x-layouts::app :title="__('Registrations') . ' — ' . $event->title">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Registrations') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', $event)" wire:navigate icon="arrow-left">
                {{ __('Back to event') }}
            </flux:button>
            <flux:button variant="ghost" size="sm" :href="route('payments.index')" wire:navigate icon="banknotes">
                {{ __('Payments') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Registrations') }}</flux:heading>

        @if (session('status'))
            <flux:callout variant="success" class="rounded-lg">{{ session('status') }}</flux:callout>
        @endif

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
            @if ($registrations->isEmpty())
                <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No registrations yet.') }}</p>
            @else
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Rider') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Team') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Payment') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Registered at') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach ($registrations as $reg)
                            <tr>
                                <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                    <span class="font-medium">{{ $reg->rider->name }}</span>
                                    @if ($reg->rider->nickname)
                                        <span class="text-zinc-500 dark:text-zinc-400">({{ $reg->rider->nickname }})</span>
                                    @endif
                                    <br />
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reg->rider->dob?->format('d/m/Y') }} · {{ $reg->rider->gender_label ?? $reg->rider->gender }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    @if ($reg->rider->organizers->isNotEmpty())
                                        <span class="inline-flex flex-wrap gap-1">
                                            @foreach ($reg->rider->organizers as $org)
                                                <flux:badge color="zinc" size="sm">{{ $org->name }}</flux:badge>
                                            @endforeach
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $reg->bracket->name }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColor = match ($reg->status) {
                                            'approved' => 'green',
                                            'pending' => 'yellow',
                                            'rejected' => 'red',
                                            'cancelled' => 'zinc',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge :color="$badgeColor" size="sm">
                                        {{ $reg->status_label }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($reg->payment)
                                        @php
                                            $payColor = match ($reg->payment->status) {
                                                'approved' => 'green',
                                                'pending' => 'yellow',
                                                'rejected' => 'red',
                                                default => 'zinc',
                                            };
                                        @endphp
                                        <flux:badge :color="$payColor" size="sm">{{ $reg->payment->status_label }}</flux:badge>
                                        @if ($reg->payment->isPending())
                                            <a href="{{ route('payments.index', ['status' => 'pending']) }}" wire:navigate class="ml-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Review') }}</a>
                                        @endif
                                    @else
                                        <span class="text-zinc-400 text-sm">—</span>
                                        @if ($reg->order)
                                            <br><a href="{{ route('payment.create', ['order_id' => $reg->order->id, 'whatsapp' => $reg->rider->user->whatsapp ?? '']) }}" target="_blank" class="text-xs text-zinc-500 hover:underline">{{ __('Send payment link') }}</a>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $reg->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($reg->isPending() || $reg->isApproved() || $reg->isRejected())
                                        <form action="{{ route('registrations.update-status', $reg) }}" method="post" class="inline-flex flex-wrap gap-1">
                                            @csrf
                                            <input type="hidden" name="status" value="approved" />
                                            <flux:button variant="ghost" size="sm" type="submit">{{ __('Approve') }}</flux:button>
                                        </form>
                                        <form action="{{ route('registrations.update-status', $reg) }}" method="post" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected" />
                                            <flux:button variant="ghost" size="sm" type="submit">{{ __('Reject') }}</flux:button>
                                        </form>
                                        <form action="{{ route('registrations.update-status', $reg) }}" method="post" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="cancelled" />
                                            <flux:button variant="ghost" size="sm" type="submit">{{ __('Cancel') }}</flux:button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-2">
                    {{ $registrations->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
