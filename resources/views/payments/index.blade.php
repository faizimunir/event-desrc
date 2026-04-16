<x-layouts::app :title="__('Payments')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Payments') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <flux:heading>{{ __('Payments') }}</flux:heading>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Verify transfer proofs and approve or reject payments.') }}
        </p>

        @if (session('status'))
            <flux:callout variant="success" class="rounded-lg">{{ session('status') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" class="rounded-lg">{{ session('error') }}</flux:callout>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('payments.index') }}"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ !request()->query('status') ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                {{ __('All') }}
            </a>
            @foreach (\App\Models\Payment::STATUSES as $s)
                <a href="{{ route('payments.index', ['status' => $s]) }}"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->query('status') === $s ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                    {{ __(ucfirst($s)) }}
                </a>
            @endforeach
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
            @if ($payments->isEmpty())
                <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No payments found.') }}</p>
            @else
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Event') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Rider') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Proof') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Submitted') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                    <a href="{{ route('events.show', [$payment->registration->event, 'tab' => 'registrations']) }}" wire:navigate class="font-medium hover:underline">
                                        {{ $payment->registration->event->title }}
                                    </a>
                                    <br>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $payment->registration->bracket->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $payment->registration->rider->name }}
                                    @if ($payment->registration->rider->nickname)
                                        <span class="text-zinc-500">({{ $payment->registration->rider->nickname }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $payment->formatted_amount }}
                                    @if ($payment->method === 'manual' && $payment->manual_transfer_amount && $payment->isPending())
                                        <span class="mt-1 block text-xs font-normal text-amber-700 dark:text-amber-300">
                                            {{ __('Transfer') }}: {{ $payment->formatted_manual_transfer_amount }}
                                            @if ($payment->manualUniqueSuffixFormatted())
                                                · {{ __('Code') }} {{ $payment->manualUniqueSuffixFormatted() }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($payment->transfer_proof_url)
                                        <a href="{{ $payment->transfer_proof_url }}" target="_blank" rel="noopener"
                                            class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 text-sm font-medium">
                                            {{ __('View') }}
                                        </a>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColor = match ($payment->status) {
                                            'success' => 'green',
                                            'pending', 'submitted' => 'yellow',
                                            'failed' => 'red',
                                            'void', 'refunded', 'expired', 'cancelled' => 'zinc',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge :color="$badgeColor" size="sm">
                                        {{ $payment->status_label }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $payment->created_at->format('d/m/Y H:i') }}
                                    @if ($payment->reviewed_at)
                                        <br><span class="text-xs text-zinc-500">{{ __('Reviewed') }} {{ $payment->reviewed_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($payment->isPending() || $payment->isSubmitted())
                                        <form action="{{ route('payments.approve', $payment) }}" method="post" class="inline">
                                            @csrf
                                            <flux:button variant="ghost" size="sm" type="submit" color="green">{{ __('Approve') }}</flux:button>
                                        </form>
                                        <form action="{{ route('payments.reject', $payment) }}" method="post" class="inline" x-data="{ open: false }">
                                            @csrf
                                            <flux:button variant="ghost" size="sm" type="button" color="red" @click="open = !open">{{ __('Reject') }}</flux:button>
                                            <div x-show="open" x-cloak class="mt-2 text-left">
                                                <label for="reject-notes-{{ $payment->id }}" class="block text-xs text-zinc-500 dark:text-zinc-400">{{ __('Notes (optional)') }}</label>
                                                <textarea name="admin_notes" id="reject-notes-{{ $payment->id }}" rows="2" class="mt-1 block w-48 rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm"></textarea>
                                                <flux:button variant="ghost" size="sm" type="submit" color="red" class="mt-1">{{ __('Confirm reject') }}</flux:button>
                                            </div>
                                        </form>
                                        <form action="{{ route('payments.expire', $payment) }}" method="post" class="inline" onsubmit="return confirm('{{ __('Mark as expired and create new order ID for this registration? Old order ID will no longer work.') }}');">
                                            @csrf
                                            <flux:button variant="ghost" size="sm" type="submit" color="zinc">{{ __('Expire') }}</flux:button>
                                        </form>
                                    @else
                                        @if ($payment->admin_notes)
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400" title="{{ $payment->admin_notes }}">{{ __('Has notes') }}</span>
                                        @else
                                            —
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-2">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
