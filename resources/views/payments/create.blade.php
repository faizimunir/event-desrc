<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Payment') . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Payment') }}
                </h1>
                <div class="flex items-center gap-2">
                    @php
                        $paymentStatusBadge = $registration?->payment?->status_label;
                        $orderForChange = $registration?->order;
                        $canChangeMethod = $orderForChange && ($registration?->payment?->isPending() ?? false) && ($orderForChange?->isPendingUnpaid() ?? false);
                    @endphp
                    @if ($canChangeMethod)
                        <flux:button
                            href="{{ route('orders.show', $orderForChange) . '?change_payment_method=1' }}"
                            variant="ghost"
                        >
                            {{ __('Change payment method') }}
                        </flux:button>
                    @endif
                    @if ($paymentStatusBadge)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                            @if (($registration?->payment?->isSuccess() ?? false)) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                            @elseif (($registration?->payment?->isFailed() ?? false) || ($registration?->payment?->isExpired() ?? false) || ($registration?->payment?->isCancelled() ?? false)) bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                            @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                            @endif">
                            {{ $paymentStatusBadge }}
                        </span>
                    @endif
                </div>
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if (($allowsManual ?? false) && ($allowsQris ?? false))
                    {{ __('You can pay with QRIS / automatic (Moota) or manual transfer and proof upload—use whichever you prefer.') }}
                @elseif ($allowsQris ?? false)
                    {{ __('Complete payment using QRIS / automatic transfer (Moota) as shown below.') }}
                @elseif ($allowsManual ?? false)
                    {{ __('Transfer to the selected account and upload your transfer proof.') }}
                @else
                    {{ __('Follow the instructions below to complete payment.') }}
                @endif
            </p>

            @if (session('status'))
                <div class="mt-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mt-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            @if ($orderExpiredOrCancelled ?? false)
                <div class="mt-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        {{ __('This order has expired or was cancelled.') }}
                    </p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        {{ __('Please register again for the event to get a new order.') }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <flux:button href="{{ route('orders.index') }}" variant="primary">
                            {{ __('My orders') }}
                        </flux:button>
                        <flux:button href="{{ route('home') }}" variant="ghost">
                            {{ __('Back to home') }}
                        </flux:button>
                    </div>
                </div>
            @elseif (!$registration)
                {{-- Step 1: Verifikasi order (Order code + WhatsApp) --}}
                <form action="{{ route('payment.verify') }}" method="post" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="order_code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Order code') }}
                        </label>
                        <input type="text" name="order_code" id="order_code" value="{{ old('order_code', $orderCode) }}"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 font-mono"
                            placeholder="ORD-01HY1X7B1K4X8M2W6J7N9F3P5A" required>
                        @error('order_code')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('WhatsApp number') }}
                        </label>
                        <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $whatsapp) }}"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            placeholder="{{ __('e.g. 08123456789') }}" required>
                        @error('whatsapp')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Use the same number you used when registering.') }}
                        </p>
                    </div>
                    @if ($errors->any() && !$errors->has('order_code') && !$errors->has('whatsapp'))
                        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <flux:button type="submit" variant="primary">
                        {{ __('Continue') }}
                    </flux:button>
                </form>
            @else
                {{-- Step 2: Tampilkan rekening + form upload bukti --}}
                @php
                    $payment = $registration->payment;
                    $amount = $registration->package ? $registration->package->payableAmount() : 0;
                    $proofSubmitted = $payment && $payment->isPending() && $payment->transfer_proof_path;
                    $showProofCountdown = $payment && $payment->isPending() && $payment->expires_at && !$proofSubmitted;
                    $isMoota = $payment && $payment->method === 'moota';
                    $mootaBank = \App\Models\Payment::getMootaBankInfo();
                    $allowsManual = $allowsManual ?? false;
                    $allowsQris = $allowsQris ?? false;
                    $manualAccounts = $manualAccounts ?? collect();
                    $manualMisconfigured = $allowsManual && $manualAccounts->isEmpty();
                    $mootaPendingBlocksManual = $payment && $payment->method === 'moota' && $payment->isPending();
                    $showQrisFirst = ($preferredPaymentMethod ?? null) !== 'manual';
                    $lockToSelectedMethod = $payment && $payment->isPending() && is_string($payment->method) && $payment->method !== '';
                    $hideManualForQrisChoice = ($preferredPaymentMethod ?? null) === 'qris' && $allowsQris;
                @endphp

                <div class="mt-6 space-y-6">
                    @if ($allowsManual && ! $allowsQris && $manualMisconfigured)
                        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                {{ __('Manual payment is enabled for this event, but no bank account has been assigned. Please contact the organizer.') }}
                            </p>
                        </div>
                    @endif
                    @if ($proofSubmitted)
                        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                {{ __('Status') }}: {{ __('Submitted') }}
                            </p>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                Pembayaran sedang di review oleh admin. Tunggu 1 x 24 jam.
                            </p>
                        </div>
                    @elseif ($showProofCountdown && !($payment && $payment->method === 'moota'))
                        <div
                            class="mb-4"
                            data-expires-at="{{ $payment->expires_at->format('c') }}"
                            data-time-up="{{ __('Time\'s up') }}"
                            data-upload-within="{{ __('Upload proof within') }}"
                            x-data="paymentProofCountdown()"
                            x-init="init()">
                            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                    <flux:icon name="clock" class="mr-1.5 inline-block size-4 align-middle" />
                                    <span x-text="text"></span>
                                </p>
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                    {{ __('Payment will expire if proof is not uploaded in time.') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if (!$proofSubmitted && !($payment && $payment->isSuccess()))
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Registration') }}
                        </p>
                        <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $registration->event->title }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $registration->rider->name }} · {{ $registration->bracket->name }}
                            @if ($registration->package)
                                · {{ $registration->package->name }}
                            @endif
                        </p>
                        <p class="mt-2 text-lg font-semibold text-amber-600 dark:text-amber-400">
                            {{ 'Rp ' . number_format((float) $amount, 0, ',', '.') }}
                        </p>
                    </div>

                    @if (($preferredPaymentMethod ?? null) === 'manual' && $allowsManual && $allowsQris)
                        <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-900/15 p-3 text-sm text-amber-900 dark:text-amber-100">
                            {{ __('You chose manual transfer. Complete the steps below, or use Moota / QRIS instead if you prefer.') }}
                        </div>
                    @elseif (($preferredPaymentMethod ?? null) === 'qris' && $allowsManual && $allowsQris)
                        <div class="rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50/80 dark:bg-violet-900/15 p-3 text-sm text-violet-900 dark:text-violet-100">
                            {{ __('You chose QRIS / Moota. Use the Moota section first, or pay manually below if you change your mind.') }}
                        </div>
                    @endif

                    <div class="flex flex-col gap-6">
                    @if ($allowsQris && (! $lockToSelectedMethod || ($payment && $payment->method === 'moota')))
                    <div class="rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 p-4 {{ $showQrisFirst ? 'order-1' : 'order-2' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">
                                    {{ __('Pay via Moota (QRIS / bank transfer)') }}
                                </p>
                                <p class="mt-1 text-sm text-violet-800 dark:text-violet-200">
                                    {{ __('Transfer the exact amount below to our account. Confirmation is automatic when the transfer appears in Moota.') }}
                                </p>
                            </div>
                            <form action="{{ route('payment.moota.confirm') }}" method="post">
                                @csrf
                                <input type="hidden" name="order_code" value="{{ $orderCode }}">
                                <input type="hidden" name="whatsapp" value="{{ $whatsapp }}">
                                <flux:button type="submit" variant="primary">
                                    {{ $isMoota && $payment->moota_transfer_amount ? __('Show transfer details') : __('Use Moota') }}
                                </flux:button>
                            </form>
                        </div>

                        @if ($isMoota && $payment->moota_transfer_amount)
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-violet-200/70 dark:border-violet-800/70 bg-white/60 dark:bg-zinc-900/40 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">
                                        {{ __('Transfer to') }}
                                    </p>
                                    <p class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $mootaBank['bank_name'] }}</p>
                                    <p class="text-lg font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ $mootaBank['account_number'] }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $mootaBank['account_holder'] }}</p>
                                </div>
                                <div class="rounded-lg border border-violet-200/70 dark:border-violet-800/70 bg-white/60 dark:bg-zinc-900/40 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">
                                        {{ __('Exact amount (IDR)') }}
                                    </p>
                                    <p class="mt-1 text-2xl font-bold font-mono text-violet-800 dark:text-violet-200">
                                        {{ 'Rp ' . number_format((float) $payment->moota_transfer_amount, 0, ',', '.') }}
                                    </p>
                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Transfer this exact amount so we can match your payment. Do not round.') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif

                    @if (! $hideManualForQrisChoice && $allowsManual && (! $lockToSelectedMethod || ($payment && $payment->method === 'manual')) && ! $mootaPendingBlocksManual && ! $manualMisconfigured)
                    <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 {{ $showQrisFirst ? 'order-2' : 'order-1' }}">
                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
                            {{ __('Manual transfer') }}
                        </p>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                            @if ($manualAccounts->count() > 1)
                                {{ __('Choose a bank account below when uploading your proof (if more than one is offered).') }}
                            @else
                                {{ __('Transfer to the account below, then upload your proof.') }}
                            @endif
                        </p>
                        @if ($manualAccounts->count() === 1)
                            @php $acc = $manualAccounts->first(); @endphp
                            <p class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $acc->acc_bank }}</p>
                            <p class="text-lg font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ $acc->acc_number }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $acc->acc_name }}</p>
                        @elseif ($manualAccounts->count() > 1)
                            <ul class="mt-3 space-y-3">
                                @foreach ($manualAccounts as $acc)
                                    <li class="rounded-lg border border-amber-200/70 dark:border-amber-800/70 bg-white/60 dark:bg-zinc-900/40 p-3">
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $acc->acc_bank }}</p>
                                        <p class="font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $acc->acc_number }}</p>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $acc->acc_name }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $bank['bank_name'] }}</p>
                            <p class="text-lg font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ $bank['account_number'] }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $bank['account_holder'] }}</p>
                        @endif

                        @if ($payment && $payment->method === 'manual' && $payment->manual_transfer_amount && ! $proofSubmitted)
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @if ($payment->manualUniqueSuffixFormatted())
                                    <div class="rounded-lg border border-amber-200/70 dark:border-amber-800/70 bg-white/60 dark:bg-zinc-900/40 p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                            {{ __('Unique payment code') }}
                                        </p>
                                        <p class="mt-1 text-2xl font-bold font-mono tracking-widest text-amber-800 dark:text-amber-200">
                                            {{ $payment->manualUniqueSuffixFormatted() }}
                                        </p>
                                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('This 3-digit code (001–999) is added to the rounded package price so your transfer amount is unique.') }}
                                        </p>
                                    </div>
                                @endif
                                <div class="rounded-lg border border-amber-200/70 dark:border-amber-800/70 bg-white/60 dark:bg-zinc-900/40 p-3 sm:col-span-1 {{ ! $payment->manualUniqueSuffixFormatted() ? 'sm:col-span-2' : '' }}">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                        {{ __('Exact amount to transfer (IDR)') }}
                                    </p>
                                    <p class="mt-1 text-2xl font-bold font-mono text-amber-800 dark:text-amber-200">
                                        {{ $payment->formatted_manual_transfer_amount }}
                                    </p>
                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Transfer this exact total so we can identify your payment. Do not round.') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif
                    </div>

                    @endif

                    @if ($payment && $payment->isSuccess())
                        <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                            {{ __('Your payment has been verified.') }}
                        </div>
                    @elseif ($payment && $payment->isFailed())
                        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-200">
                            {{ __('This payment was rejected.') }}
                            @if ($payment->admin_notes)
                                <p class="mt-2">{{ $payment->admin_notes }}</p>
                            @endif
                        </div>
                    @elseif (! $hideManualForQrisChoice && $allowsManual && (! $lockToSelectedMethod || ($payment && $payment->method === 'manual')) && ! $manualMisconfigured && ! $proofSubmitted && ! $mootaPendingBlocksManual)
                        <form action="{{ route('payment.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="order_code" value="{{ $orderCode }}">
                            <input type="hidden" name="whatsapp" value="{{ $whatsapp }}">
                            @if ($manualAccounts->count() === 1)
                                <input type="hidden" name="manual_account_id" value="{{ $manualAccounts->first()->id }}">
                            @elseif ($manualAccounts->count() > 1)
                                <fieldset class="space-y-2">
                                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Transfer to which account?') }}</legend>
                                    @foreach ($manualAccounts as $acc)
                                        <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-600 has-[:checked]:border-amber-500 has-[:checked]:ring-1 has-[:checked]:ring-amber-500">
                                            <input type="radio" name="manual_account_id" value="{{ $acc->id }}" class="mt-1" required
                                                @checked((string) old('manual_account_id') === (string) $acc->id)>
                                            <span>
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $acc->acc_bank }}</span>
                                                <span class="block font-mono text-sm text-zinc-800 dark:text-zinc-200">{{ $acc->acc_number }}</span>
                                                <span class="block text-sm text-zinc-600 dark:text-zinc-400">{{ $acc->acc_name }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </fieldset>
                                @error('manual_account_id')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            @endif

                            <div>
                                <label for="transfer_proof" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Transfer proof') }} ({{ __('photo or screenshot') }})
                                </label>
                                <input type="file" name="transfer_proof" id="transfer_proof" accept="image/*"
                                    class="mt-1 block w-full text-sm text-zinc-600 dark:text-zinc-400 file:mr-4 file:rounded-lg file:border-0 file:bg-amber-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-amber-700"
                                    required>
                                @error('transfer_proof')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Max 3 MB. JPG, PNG.') }}</p>
                            </div>

                            <flux:button type="submit" variant="primary">
                                {{ __('Upload proof') }}
                            </flux:button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <p class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                {{ __('Back to home') }}
            </a>
        </p>
    </main>

    @include('partials.footer')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentProofCountdown', () => ({
                expiresAt: null,
                text: '—',
                init() {
                    const el = this.$el;
                    this.expiresAt = new Date(el.dataset.expiresAt);
                    const timeUpLabel = el.dataset.timeUp || "Time's up";
                    const uploadWithinLabel = el.dataset.uploadWithin || "Upload proof within";
                    const update = () => {
                        const sec = Math.max(0, Math.floor((this.expiresAt - new Date()) / 1000));
                        if (sec <= 0) {
                            this.text = timeUpLabel;
                            return;
                        }
                        const m = Math.floor(sec / 60);
                        const s = sec % 60;
                        this.text = uploadWithinLabel + ' ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    };
                    update();
                    setInterval(update, 1000);
                }
            }));
        });
    </script>
    @fluxScripts
</body>
</html>
