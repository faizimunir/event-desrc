<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;

/**
 * Session claim: parent sudah verifikasi kepemilikan nomor WhatsApp untuk alur registrasi publik.
 * Dipakai agar rider ke-2+ di sesi yang sama tidak perlu OTP ulang.
 */
class RegistrationParentClaim
{
    public const CLAIM_KEY = 'registration_parent_claim';

    public const PREFILL_KEY = 'registration_parent_prefill';

    public static function isVerified(string $normalizedWhatsapp): bool
    {
        $claim = session(self::CLAIM_KEY);
        if (! is_array($claim)) {
            return false;
        }

        return ($claim['whatsapp'] ?? null) === $normalizedWhatsapp
            && filled($claim['user_id'] ?? null);
    }

    public static function markVerified(User $user, string $normalizedWhatsapp): void
    {
        session([
            self::CLAIM_KEY => [
                'whatsapp' => $normalizedWhatsapp,
                'user_id' => $user->id,
                'verified_at' => now()->timestamp,
            ],
        ]);
    }

    public static function rememberPrefill(User $user, Event $event, string $normalizedWhatsapp): void
    {
        self::markVerified($user, $normalizedWhatsapp);

        session([
            self::PREFILL_KEY => [
                'whatsapp' => $normalizedWhatsapp,
                'parent_name' => $user->name,
                'event_id' => $event->id,
                'event_slug' => $event->slug,
            ],
        ]);
    }

    /**
     * @return array{whatsapp: string, parent_name: string, event_id: int, event_slug: string}|null
     */
    public static function prefill(?int $eventId = null): ?array
    {
        $prefill = session(self::PREFILL_KEY);
        if (! is_array($prefill) || empty($prefill['whatsapp'])) {
            return null;
        }

        if ($eventId !== null && (int) ($prefill['event_id'] ?? 0) !== $eventId) {
            return null;
        }

        return [
            'whatsapp' => (string) $prefill['whatsapp'],
            'parent_name' => (string) ($prefill['parent_name'] ?? ''),
            'event_id' => (int) ($prefill['event_id'] ?? 0),
            'event_slug' => (string) ($prefill['event_slug'] ?? ''),
        ];
    }

    public static function clear(): void
    {
        session()->forget([self::CLAIM_KEY, self::PREFILL_KEY]);
    }
}
