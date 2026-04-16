<?php

namespace App\Services;

use RuntimeException;

/**
 * SNAP "Asymmetric Without Get Token" — lihat dokumentasi Winpay Signature Generation.
 */
class WinpaySnapSignature
{
    public static function hashRequestBody(string $jsonMinified): string
    {
        return strtolower(bin2hex(hash('sha256', $jsonMinified, true)));
    }

    /**
     * @param  resource  $privateKey  openssl private key resource
     */
    public static function signString(string $stringToSign, $privateKey): string
    {
        $signature = '';
        if (! openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('openssl_sign failed for Winpay request.');
        }

        return base64_encode($signature);
    }

    public static function buildStringToSign(string $httpMethod, string $endpointPath, string $jsonMinified, string $timestamp): string
    {
        $hashed = self::hashRequestBody($jsonMinified);

        return implode(':', [
            strtoupper($httpMethod),
            $endpointPath,
            $hashed,
            $timestamp,
        ]);
    }
}
