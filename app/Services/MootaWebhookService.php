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
        $mutationId = $mutation['mutation_id'] ?? $mutation['token'] ?? null;
        if (! is_string($mutationId) || $mutationId === '') {
            return;
        }

        if (Payment::query()->where('moota_mutation_id', $mutationId)->exists()) {
            return;
        }

        $type = $mutation['type'] ?? null;
        if ($type !== 'CR') {
            return;
        }

        $amount = $this->normalizeAmount($mutation['amount'] ?? null);
        if ($amount === null || (float) $amount <= 0) {
            return;
        }

        $payment = Payment::query()
            ->where('method', Payment::METHOD_QRIS)
            ->where('status', Payment::STATUS_PENDING)
            ->where('amount', $amount)
            ->orderBy('id')
            ->first();

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
}
