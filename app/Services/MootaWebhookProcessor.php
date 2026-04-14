<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class MootaWebhookProcessor
{
    public function processEventId(int $eventId): void
    {
        $row = DB::table('moota_webhook_events')->where('id', $eventId)->first();
        if (! $row) {
            return;
        }

        if (($row->status ?? null) === 'processed') {
            return;
        }

        DB::table('moota_webhook_events')->where('id', $eventId)->update([
            'status' => 'processing',
            'attempts' => (int) ($row->attempts ?? 0) + 1,
            'updated_at' => now(),
        ]);

        try {
            $items = is_string($row->raw_body ?? null) ? json_decode($row->raw_body, true) : null;
            if (! is_array($items)) {
                throw new \RuntimeException('Invalid JSON payload');
            }

            $this->processItems($items, $eventId);

            DB::table('moota_webhook_events')->where('id', $eventId)->update([
                'status' => 'processed',
                'processed_at' => now(),
                'last_error' => null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            DB::table('moota_webhook_events')->where('id', $eventId)->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function processItems(array $items, int $eventId): void
    {
        $mode = (string) config('moota.webhook_mode', 'settle');
        $recordOnly = $mode === 'record_only';

        $expectedAccount = (string) config('moota.expected_account_number', '');
        $expectedAccount = $expectedAccount !== '' ? preg_replace('/\s+/', '', $expectedAccount) : '';

        foreach ($items as $mutation) {
            if (! is_array($mutation)) {
                continue;
            }

            $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
            if (! $mutationId) {
                continue;
            }
            $mutationId = (string) $mutationId;

            if ($expectedAccount !== '') {
                $incoming = preg_replace('/\s+/', '', (string) ($mutation['account_number'] ?? ''));
                if ($incoming !== '' && $incoming !== $expectedAccount) {
                    continue;
                }
            }

            $this->persistSettlementRecord($eventId, $mutation, $mutationId);

            if ($recordOnly) {
                continue;
            }

            if (Payment::query()->where('moota_mutation_id', $mutationId)->exists()) {
                continue;
            }

            if (($mutation['type'] ?? '') !== 'CR') {
                continue;
            }

            $amount = round((float) ($mutation['amount'] ?? 0), 2);
            $orderId = trim((string) data_get($mutation, 'payment_detail.order_id', ''));

            DB::transaction(function () use ($mutation, $mutationId, $amount, $orderId) {
                $paymentQuery = Payment::query()
                    ->where('method', 'moota')
                    ->where('status', Payment::STATUS_PENDING)
                    ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_UNPAID));

                if ($orderId !== '') {
                    $paymentQuery->whereHas('order', fn ($q) => $q->where('order_code', $orderId));
                } else {
                    $paymentQuery->where('moota_transfer_amount', $amount);
                }

                $payment = $paymentQuery->lockForUpdate()->first();
                if (! $payment || $payment->isSuccess()) {
                    return;
                }

                $order = $payment->order;
                if (! $order) {
                    return;
                }
                Order::query()->whereKey($order->getKey())->lockForUpdate()->first();
                $order->refresh();
                if ($order->isPaid()) {
                    return;
                }

                $payment->forceFill([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                    'moota_mutation_id' => $mutationId,
                    'moota_raw' => $mutation,
                    'admin_notes' => trim('Moota: mutation '.$mutationId),
                    'reviewed_at' => now(),
                    'reviewed_by' => null,
                    'expires_at' => null,
                ])->save();

                $order->update([
                    'status' => Order::STATUS_PAID,
                    'paid_at' => now(),
                ]);

                DB::table('moota_settlement_records')->where('mutation_id', $mutationId)->update([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'updated_at' => now(),
                ]);

                TicketService::ensureTicketForRegistration($payment->registration);
            });
        }
    }

    /**
     * Arsip mutasi Moota (rekonsiliasi / audit), terpisah dari status pembayaran gateway (mis. Winpay).
     *
     * @param  array<string, mixed>  $mutation
     */
    private function persistSettlementRecord(int $eventId, array $mutation, string $mutationId): void
    {
        $amount = array_key_exists('amount', $mutation)
            ? round((float) $mutation['amount'], 2)
            : null;
        $orderCodeRaw = trim((string) data_get($mutation, 'payment_detail.order_id', ''));
        $orderCode = $orderCodeRaw !== '' ? $orderCodeRaw : null;

        $order = $orderCode
            ? Order::query()->where('order_code', $orderCode)->first()
            : null;
        $payment = $order ? $order->payments()->latest('id')->first() : null;

        $now = now();
        $payload = [
            'moota_webhook_event_id' => $eventId,
            'type' => $mutation['type'] ?? null,
            'amount' => $amount,
            'order_code' => $orderCode,
            'account_number' => $mutation['account_number'] ?? null,
            'payload' => json_encode($mutation),
            'updated_at' => $now,
        ];

        if ($order) {
            $payload['order_id'] = $order->id;
            $payload['payment_id'] = $payment?->id;
        }

        if (DB::table('moota_settlement_records')->where('mutation_id', $mutationId)->exists()) {
            DB::table('moota_settlement_records')->where('mutation_id', $mutationId)->update($payload);

            return;
        }

        $payload['mutation_id'] = $mutationId;
        $payload['created_at'] = $now;
        DB::table('moota_settlement_records')->insert($payload);
    }
}
