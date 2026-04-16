<?php

namespace App\Services;

class MootaSignature
{
    public static function verify(string $rawBody, string $secret, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || $signatureHeader === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
