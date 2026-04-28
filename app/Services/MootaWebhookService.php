<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $nestedCandidates = [
            $decoded['data'] ?? null,
            $decoded['mutations'] ?? null,
            $decoded['payload'] ?? null,
        ];

        foreach ($nestedCandidates as $candidate) {
            if (! is_array($candidate) || $candidate === []) {
                continue;
            }

            if (isset($candidate['mutation_id']) && is_string($candidate['mutation_id'])) {
                return [$candidate];
            }

            $nestedFirst = reset($candidate);
            if (is_array($nestedFirst) && isset($nestedFirst['mutation_id'])) {
                return array_values(array_filter($candidate, fn ($row) => is_array($row) && isset($row['mutation_id'])));
            }
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
        if ($amount === null || $amount <= 0) {
            return;
        }

        $payment = Payment::query()
            ->where('method', Payment::METHOD_QRIS)
            ->where('status', Payment::STATUS_PENDING)
            ->where('transfer_amount', $amount)
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

        $ticketRegistrationId = null;

        DB::transaction(function () use ($payment, $mutationId, $paidAt, &$ticketRegistrationId) {
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->first();
            if (! $order) {
                return;
            }

            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                return;
            }

            if ($payment->status !== Payment::STATUS_PENDING || $payment->method !== Payment::METHOD_QRIS) {
                return;
            }

            if ($order->isPaid()) {
                return;
            }

            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => $paidAt,
                'moota_mutation_id' => $mutationId,
            ]);

            $order->refresh();
            if (! $order->isPaid()) {
                $order->update([
                    'status' => Order::STATUS_PAID,
                    'paid_at' => $paidAt,
                ]);
            }

            $ticketRegistrationId = $payment->registration_id;
        });

        if ($ticketRegistrationId !== null) {
            $registration = Registration::query()->find($ticketRegistrationId);
            if ($registration) {
                $registration->unsetRelation('payment');
                TicketService::ensureTicketForRegistration($registration);
            }
        }
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
