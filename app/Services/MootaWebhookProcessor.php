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

        // Prevent double-processing
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

            $this->processItems($items);

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
     * Proses item-item payload Moota:
     * - Dynamic QRIS: match via payment_detail.order_id (order_code)
     * - Fallback mutasi bank: match via moota_transfer_amount
     */
    private function processItems(array $items): void
    {
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

            if (Payment::query()->where('moota_mutation_id', (string) $mutationId)->exists()) {
                continue;
            }

            if (($mutation['type'] ?? '') !== 'CR') {
                continue;
            }

            if ($expectedAccount !== '') {
                $incoming = preg_replace('/\s+/', '', (string) ($mutation['account_number'] ?? ''));
                if ($incoming !== '' && $incoming !== $expectedAccount) {
                    continue;
                }
            }

            $amount = round((float) ($mutation['amount'] ?? 0), 2);
            $orderId = trim((string) data_get($mutation, 'payment_detail.order_id', ''));

            DB::transaction(function () use ($mutation, $mutationId, $amount, $orderId) {
                $paymentQuery = Payment::query()
                    ->where('method', 'moota')
                    ->where('status', Payment::STATUS_PENDING)
                    ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PENDING)
                        ->where('payment_status', Order::PAYMENT_STATUS_UNPAID));

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
                if ($order->status === Order::STATUS_CONFIRMED && $order->payment_status === Order::PAYMENT_STATUS_PAID) {
                    return;
                }

                $payment->forceFill([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                    'moota_mutation_id' => (string) $mutationId,
                    'moota_raw' => $mutation,
                    'admin_notes' => trim('Moota: mutation '.(string) $mutationId),
                    'reviewed_at' => now(),
                    'reviewed_by' => null,
                    'expires_at' => null,
                ])->save();

                $order->update([
                    'status' => Order::STATUS_CONFIRMED,
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'paid_at' => now(),
                ]);
                TicketService::ensureTicketForRegistration($payment->registration);
            });
        }
    }
}
