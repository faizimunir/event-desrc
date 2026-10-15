<?php

namespace App\Services;

use App\Models\Bracket;
use App\Models\Package;
use App\Models\Registration;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Mengalihkan seluruh pendaftar dari satu bracket/package ke bracket/package lain di event yang sama.
 *
 * Setiap pendaftar divalidasi dan dipindah di transaksinya sendiri. Pendaftar yang tidak lolos
 * tidak dipindah (tetap di sumber) dan dilaporkan beserta alasannya, tanpa menggagalkan yang lain.
 */
class RegistrationTransferService
{
    public function __construct(private readonly RegistrationEligibilityService $eligibility) {}

    /**
     * @return array{moved: int, failed: list<array{registration_id: int, rider: string, reason: string}>, price_changed: int}
     */
    public function transferBracket(Bracket $source, Bracket $target): array
    {
        if ($source->is($target)) {
            throw new DomainException(__('Source and target must be different.'));
        }
        if ((int) $source->event_id !== (int) $target->event_id) {
            throw new DomainException(__('Target must belong to the same event.'));
        }

        $result = ['moved' => 0, 'failed' => [], 'price_changed' => 0];

        $ids = Registration::query()->where('bracket_id', $source->id)->orderBy('id')->pluck('id');

        foreach ($ids as $registrationId) {
            $outcome = DB::transaction(function () use ($source, $target, $registrationId): array {
                Bracket::query()->whereKey([$source->id, $target->id])->orderBy('id')->lockForUpdate()->get();

                $registration = Registration::query()->with(['rider', 'order'])->whereKey($registrationId)->lockForUpdate()->first();
                if (! $registration || (int) $registration->bracket_id !== (int) $source->id) {
                    return ['skipped', ''];
                }

                $freshTarget = Bracket::query()->with('event')->findOrFail($target->id);

                if (Registration::query()
                    ->where('event_id', $registration->event_id)
                    ->where('rider_id', $registration->rider_id)
                    ->where('bracket_id', $freshTarget->id)
                    ->whereKeyNot($registration->id)
                    ->exists()) {
                    return ['failed', __('This rider is already registered for the target bracket.'), $registration];
                }

                // Pendaftar batal/ditolak adalah data historis: tidak perlu lolos aturan umur/gender/kuota.
                if ($this->isActive($registration)) {
                    $eligibility = $this->eligibility->checkEligibility($registration->rider, $freshTarget);
                    if (! $eligibility['eligible']) {
                        return ['failed', $eligibility['message'], $registration];
                    }
                }

                if ($this->holdsQuota($registration) && ! $freshTarget->hasQuota()) {
                    return ['failed', __('This bracket has no remaining quota.'), $registration];
                }

                $registration->update(['bracket_id' => $freshTarget->id]);

                return ['moved', '', $registration];
            });

            $this->collect($result, $outcome, $registrationId);
        }

        return $result;
    }

    /**
     * @return array{moved: int, failed: list<array{registration_id: int, rider: string, reason: string}>, price_changed: int}
     */
    public function transferPackage(Package $source, Package $target): array
    {
        if ($source->is($target)) {
            throw new DomainException(__('Source and target must be different.'));
        }
        if ((int) $source->event_id !== (int) $target->event_id) {
            throw new DomainException(__('Target must belong to the same event.'));
        }
        if (! $target->isActive()) {
            throw new DomainException(__('Target package must be active.'));
        }

        $result = ['moved' => 0, 'failed' => [], 'price_changed' => 0];

        $ids = Registration::query()->where('package_id', $source->id)->orderBy('id')->pluck('id');

        foreach ($ids as $registrationId) {
            $outcome = DB::transaction(function () use ($source, $target, $registrationId): array {
                Package::query()->whereKey([$source->id, $target->id])->orderBy('id')->lockForUpdate()->get();

                $registration = Registration::query()->with(['rider', 'order'])->whereKey($registrationId)->lockForUpdate()->first();
                if (! $registration || (int) $registration->package_id !== (int) $source->id) {
                    return ['skipped', ''];
                }

                $freshTarget = Package::query()->with('rewards')->findOrFail($target->id);
                if (! $freshTarget->isActive()) {
                    return ['failed', __('Target package must be active.'), $registration];
                }

                if ($this->isActive($registration) && $freshTarget->hasJerseyReward() && blank($registration->jersey_size)) {
                    return ['failed', __('Jersey size is required for the target package.'), $registration];
                }

                if ($this->holdsQuota($registration) && $freshTarget->isQuotaFull()) {
                    return ['failed', __('This package has no remaining quota.'), $registration];
                }

                $registration->update(['package_id' => $freshTarget->id]);

                $priceChanged = abs($freshTarget->payableAmount() - $source->payableAmount()) > 0.001;

                return ['moved', '', $registration, $priceChanged];
            });

            $this->collect($result, $outcome, $registrationId);
        }

        return $result;
    }

    /** Registrasi yang masih "hidup" (pending/approved). Cancelled/rejected hanya data historis. */
    private function isActive(Registration $registration): bool
    {
        return in_array($registration->status, [Registration::STATUS_PENDING, Registration::STATUS_APPROVED], true);
    }

    /** Sama dengan aturan hitung kuota di Bracket::registeredCount() dan Package::remainingQuota(). */
    private function holdsQuota(Registration $registration): bool
    {
        return Registration::query()
            ->whereKey($registration->id)
            ->countsTowardQuota()
            ->whereHas('order', fn ($q) => $q->holdsQuota())
            ->exists();
    }

    /**
     * @param  array{moved: int, failed: list<array{registration_id: int, rider: string, reason: string}>, price_changed: int}  $result
     * @param  array<int, mixed>  $outcome
     */
    private function collect(array &$result, array $outcome, int $registrationId): void
    {
        $status = $outcome[0];

        if ($status === 'moved') {
            $result['moved']++;
            if (($outcome[3] ?? false) === true) {
                $result['price_changed']++;
            }

            return;
        }

        if ($status === 'failed') {
            /** @var Registration $registration */
            $registration = $outcome[2];
            $result['failed'][] = [
                'registration_id' => $registrationId,
                'rider' => $registration->rider?->name ?? __('Rider'),
                'reason' => $outcome[1],
            ];
        }
    }
}
