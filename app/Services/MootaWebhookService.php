<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sama inti Herd/deltae {@see \App\Services\MootaWebhookService}:
 * verifikasi Hmac-SHA256, normalisasi payload, mutasi tipe CR + nominal `amount`
 * → cocokkan `payments.amount` (QRIS, pending) → tandai sukses.
 */
class MootaWebhookService
{
    public function verifySignature(?string $signature, string $payload, string $secret): bool
    {
        if ($signature === null || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizePayload(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        if ($decoded === []) {
            return [];
        }

        if (isset($decoded['mutation_id']) && is_string($decoded['mutation_id'])) {
            return [$decoded];
        }

        $first = reset($decoded);
        if (is_array($first) && isset($first['mutation_id'])) {
            return array_values(array_filter($decoded, fn ($row) => is_array($row) && isset($row['mutation_id'])));
        }

        return [];
    }

    public function processMutation(array $mutation): void
    {
        Log::info('MOOTA RAW MUTATION', [
            'mutation' => $mutation,
        ]);

        $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
        if (! is_string($mutationId) || $mutationId === '') {
            return;
        }

        if (Payment::query()->where('moota_mutation_id', $mutationId)->exists()) {
            return;
        }

        $type = $mutation['type'] ?? null;
        Log::info('MOOTA TYPE CHECK', [
            'type' => $type,
        ]);

        if ($type !== 'CR') {
            return;
        }

        $amount = $this->normalizeAmount($mutation['amount'] ?? null);
        Log::info('MOOTA AMOUNT CHECK', [
            'amount' => $amount,
        ]);

        if ($amount === null || (float) $amount <= 0) {
            return;
        }

        $orderReference = $this->extractOrderReference($mutation);
        if ($orderReference !== null) {
            Log::info('MOOTA REFERENCE CHECK', [
                'order_reference' => $orderReference,
            ]);
        }

        $payment = $orderReference ? $this->findPendingQrisPaymentByReference($orderReference) : null;
        if ($payment && (float) $payment->amount !== (float) $amount) {
            Log::warning('moota.webhook.amount_mismatch_with_reference', [
                'mutation_id' => $mutationId,
                'order_reference' => $orderReference,
                'expected_amount' => number_format((float) $payment->amount, 2, '.', ''),
                'incoming_amount' => $amount,
            ]);
        }

        Log::info('MOOTA MATCH QUERY', [
            'amount' => $amount,
            'method_constant' => Payment::METHOD_QRIS,
        ]);

        // Fallback legacy: exact amount match only when reference is absent / not resolvable.
        if (! $payment) {
            $payment = Payment::query()
                ->where('method', Payment::METHOD_QRIS)
                ->where('status', Payment::STATUS_PENDING)
                ->where('amount', $amount)
                ->orderBy('id')
                ->first();
        }

        if (! $payment) {
            Log::info('moota.webhook.unmatched', [
                'mutation_id' => $mutationId,
                'amount' => $amount,
            ]);

            return;
        }

        $paidAt = $this->parseMutationDate($mutation['date'] ?? null);

        DB::transaction(function () use ($payment, $mutation, $mutationId, $paidAt) {
            $payment->refresh();

            if ($payment->status !== Payment::STATUS_PENDING || $payment->method !== Payment::METHOD_QRIS) {
                return;
            }

            $order = $payment->order;
            if (! $order) {
                return;
            }
            if ($order->isPaid()) {
                return;
            }

            $payment->forceFill([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => $paidAt,
                'moota_mutation_id' => $mutationId,
                'moota_raw' => $mutation,
                'admin_notes' => trim('Moota: mutation '.$mutationId),
                'reviewed_at' => now(),
                'reviewed_by' => null,
                'expires_at' => null,
            ])->save();

            $order->refresh();
            if (! $order->isPaid()) {
                $order->update([
                    'status' => Order::STATUS_PAID,
                    'paid_at' => $paidAt,
                ]);
            }

            TicketService::ensureTicketForRegistration($payment->registration);
        });
    }

    protected function normalizeAmount(mixed $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        if (is_int($amount) || is_float($amount)) {
            return number_format((float) $amount, 2, '.', '');
        }

        if (is_string($amount) && $amount !== '') {
            return number_format((float) $amount, 2, '.', '');
        }

        return null;
    }

    protected function parseMutationDate(mixed $date): \DateTimeInterface
    {
        if (is_string($date) && $date !== '') {
            try {
                return \Carbon\Carbon::parse($date);
            } catch (\Throwable) {
                //
            }
        }

        return now();
    }

    protected function extractOrderReference(array $mutation): ?string
    {
        $paymentDetailOrderId = $mutation['payment_detail']['order_id'] ?? null;
        if (is_string($paymentDetailOrderId) && trim($paymentDetailOrderId) !== '') {
            return trim($paymentDetailOrderId);
        }

        $trxId = $mutation['payment_detail']['trx_id'] ?? null;
        if (is_string($trxId) && trim($trxId) !== '') {
            return trim($trxId);
        }

        foreach (['description', 'note'] as $field) {
            $value = $mutation[$field] ?? null;
            if (! is_string($value) || $value === '') {
                continue;
            }

            if (preg_match('/(ORD-[A-Za-z0-9]+)/', $value, $matches) === 1) {
                return strtoupper($matches[1]);
            }
        }

        return null;
    }

    protected function findPendingQrisPaymentByReference(string $orderReference): ?Payment
    {
        $trimmed = trim($orderReference);
        if ($trimmed === '') {
            return null;
        }

        return Payment::query()
            ->where('method', Payment::METHOD_QRIS)
            ->where('status', Payment::STATUS_PENDING)
            ->whereHas('order', function ($q) use ($trimmed) {
                $q->where('order_code', $trimmed);

                if (ctype_digit($trimmed)) {
                    $q->orWhere('id', (int) $trimmed);
                }
            })
            ->orderBy('id')
            ->first();
    }
}
