<?php

namespace App\Services;

use App\Models\Bracket;
use App\Models\Order;
use App\Models\Package;
use Closure;
use Illuminate\Support\Facades\DB;

class QuotaReservationService
{
    /**
     * Transaksi + row lock untuk kuota. Urutan selalu Bracket → Package → Order (jika ada)
     * supaya dua request yang sama-sama butuh kedua sisi tidak deadlock.
     *
     * @param  ?int  $orderId  Order yang akan di-update (mis. finalize draft); null jika belum ada order.
     */
    public static function withLocks(int $bracketId, int $packageId, ?int $orderId, Closure $callback): mixed
    {
        return DB::transaction(function () use ($bracketId, $packageId, $orderId, $callback) {
            Bracket::query()->whereKey($bracketId)->lockForUpdate()->firstOrFail();
            Package::query()->whereKey($packageId)->lockForUpdate()->firstOrFail();
            if ($orderId !== null) {
                Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            }

            return $callback();
        });
    }
}
