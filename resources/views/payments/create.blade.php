<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Payment') . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Payment') }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Transfer to the account below and upload your transfer proof.') }}
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
                    $amount = $registration->package ? $registration->package->price : 0;
                    $proofSubmitted = $payment && $payment->isPending() && $payment->transfer_proof_path;
                    $showProofCountdown = $payment && $payment->isPending() && $payment->expires_at && !$proofSubmitted;
                @endphp

                <div class="mt-6 space-y-6">
                    @if ($proofSubmitted)
                        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                {{ __('Status') }}: {{ __('Submitted') }}
                            </p>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                Pembayaran sedang di review oleh admin. Tunggu 1 x 24 jam.
                            </p>
                        </div>
                    @elseif ($showProofCountdown)
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

                    <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">
                            {{ __('Transfer to') }}
                        </p>
                        @if ($account)
                            <p class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $account->acc_bank }}</p>
                            <p class="text-lg font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ $account->acc_number }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $account->acc_name }}</p>
                        @else
                            <p class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $bank['bank_name'] }}</p>
                            <p class="text-lg font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ $bank['account_number'] }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $bank['account_holder'] }}</p>
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
                    @elseif (!$proofSubmitted)
                        <form action="{{ route('payment.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="order_code" value="{{ $orderCode }}">
                            <input type="hidden" name="whatsapp" value="{{ $whatsapp }}">

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
