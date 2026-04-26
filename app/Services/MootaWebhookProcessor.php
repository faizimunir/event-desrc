<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class MootaWebhookProcessor
{
    /**
     * Samakan deltae `MootaWebhookService::normalizePayload`: satu objek mutasi JSON atau array of objects.
     * Tanpa ini, payload satu objek `{ "mutation_id": "..." }` jatuh ke `foreach` yang memecah jadi per-field, bukan per-mutasi.
     *
     * @return list<array<string, mixed>>
     */
    public function normalizeMutationsPayload(mixed $decoded): array
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
            return array_values(array_filter(
                $decoded,
                static fn ($row) => is_array($row) && isset($row['mutation_id'])
            ));
        }

        return [];
    }

    private function parseMoneyScalar(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            $n = (float) $value;

            return $n > 0 ? round($n, 2) : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $s = trim($value);
        if ($s === '') {
            return null;
        }

        $s = preg_replace('/[^\d.,-]/', '', $s) ?? '';
        if ($s === '' || $s === '-') {
            return null;
        }

        $hasComma = str_contains($s, ',');
        $hasDot = str_contains($s, '.');

        if ($hasComma && $hasDot) {
            // e.g. 1.234,56 (ID) vs 1,234.56 (US) — assume last separator is decimal
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma && ! $hasDot) {
            $s = str_replace(',', '.', $s);
        } elseif ($hasDot && ! $hasComma) {
            // Ambiguous: 11.574 vs 11574.50 — treat single dot with exactly 3 digits after as thousands separator
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s) === 1) {
                $s = str_replace('.', '', $s);
            }
        }

        if (! is_numeric($s)) {
            return null;
        }

        $n = (float) $s;

        return $n > 0 ? round($n, 2) : null;
    }

    /**
     * Ambil kandidat nominal kredit dari payload mutasi Moota.
     * Untuk QRIS / payment gateway, nominal "utama" kadang tidak hanya di field `amount`.
     *
     * @return list<float>
     */
    private function creditAmountCandidates(array $mutation): array
    {
        $skipKeys = [
            'balance', 'saldo',
            'mutation_id', 'token', 'bank_id', 'account_number',
        ];

        $out = [];

        $walker = function (mixed $node, ?string $key = null) use (&$walker, &$out, $skipKeys): void {
            if ($key !== null) {
                $lk = strtolower((string) $key);
                foreach ($skipKeys as $sk) {
                    if ($lk === $sk) {
                        return;
                    }
                }
            }

            if (is_array($node)) {
                foreach ($node as $k => $v) {
                    $walker($v, is_string($k) ? $k : null);
                }

                return;
            }

            if ($key === null) {
                return;
            }

            $lk = strtolower((string) $key);
            $looksMoneyKey = str_contains($lk, 'amount')
                || str_contains($lk, 'total')
                || str_contains($lk, 'nominal')
                || str_contains($lk, 'gross')
                || str_contains($lk, 'nett')
                || str_contains($lk, 'net')
                || str_contains($lk, 'credit')
                || str_contains($lk, 'debit');

            if (! $looksMoneyKey) {
                return;
            }

            $parsed = $this->parseMoneyScalar($node);
            if ($parsed !== null) {
                $out[] = $parsed;
            }
        };

        $walker($mutation, null);

        // Selalu sertakan `amount` top-level jika ada (meskipun key tidak match pola).
        if (array_key_exists('amount', $mutation)) {
            $n = round((float) $mutation['amount'], 2);
            if ($n > 0) {
                $out[] = $n;
            }
        }

        $out = array_values(array_unique($out));
        rsort($out, SORT_NUMERIC);

        return $out;
    }

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

            $mutations = $this->normalizeMutationsPayload($items);
            if ($mutations === []) {
                DB::table('moota_webhook_events')->where('id', $eventId)->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

                return;
            }

            $this->processItems($mutations, $eventId);

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

            $orderId = trim((string) data_get($mutation, 'payment_detail.order_id', ''));
            $candidates = $this->creditAmountCandidates($mutation);

            DB::transaction(function () use ($mutation, $mutationId, $candidates, $orderId) {
                $paymentQuery = Payment::query()
                    ->where('method', 'moota')
                    ->where('status', Payment::STATUS_PENDING)
                    ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_UNPAID));

                if ($orderId !== '') {
                    $paymentQuery->whereHas('order', fn ($q) => $q->where('order_code', $orderId));
                } elseif ($candidates !== []) {
                    $paymentQuery->whereIn('moota_transfer_amount', $candidates);
                } else {
                    $paymentQuery->where('moota_transfer_amount', round((float) ($mutation['amount'] ?? 0), 2));
                }

                $payment = $paymentQuery->lockForUpdate()->first();

                // Fallback: QRIS kadang kirim nominal di field nested, bukan di `amount`.
                if (! $payment && $orderId === '' && $candidates !== []) {
                    $payments = Payment::query()
                        ->where('method', 'moota')
                        ->where('status', Payment::STATUS_PENDING)
                        ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_UNPAID))
                        ->with('order')
                        ->orderByDesc('id')
                        ->get();

                    foreach ($candidates as $cand) {
                        foreach ($payments as $p) {
                            $expected = $p->moota_transfer_amount !== null
                                ? round((float) $p->moota_transfer_amount, 2)
                                : null;
                            if ($expected === null) {
                                continue;
                            }
                            if (abs($cand - $expected) < 0.01) {
                                $payment = $p;

                                break 2;
                            }

                            $base = round((float) $p->amount, 2);
                            $suffix = $cand - $base;
                            if ($suffix >= Payment::MANUAL_UNIQUE_SUFFIX_MIN
                                && $suffix <= Payment::MANUAL_UNIQUE_SUFFIX_MAX
                                && abs($base + $suffix - $expected) < 0.01) {
                                $payment = $p;

                                break 2;
                            }
                        }
                    }
                }

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
     * Arsip mutasi Moota (rekonsiliasi / audit).
     *
     * @param  array<string, mixed>  $mutation
     */
    private function persistSettlementRecord(int $eventId, array $mutation, string $mutationId): void
    {
        $candidates = $this->creditAmountCandidates($mutation);
        $amount = $candidates[0] ?? (array_key_exists('amount', $mutation)
            ? round((float) $mutation['amount'], 2)
            : null);
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
