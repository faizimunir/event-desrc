<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Resolve queue connection dengan fallback aman saat Redis/ext tidak tersedia (lokal XAMPP).
 * Production yang punya Redis tetap memakai koneksi yang dikonfigurasi.
 */
final class QueueConnection
{
    public static function resolve(?string $preferred = null, string $context = 'queue'): string
    {
        $preferred = $preferred !== null && $preferred !== ''
            ? $preferred
            : (string) config('queue.default', 'database');

        if (self::connectionUsable($preferred)) {
            return $preferred;
        }

        foreach (['database', 'sync', (string) config('queue.default', 'sync')] as $candidate) {
            if ($candidate === '' || $candidate === $preferred) {
                continue;
            }

            if (self::connectionUsable($candidate)) {
                Log::warning("{$context}: Redis queue unavailable, falling back", [
                    'from' => $preferred,
                    'to' => $candidate,
                    'hint' => 'Local: set WHACENTER_QUEUE_CONNECTION=database (and MOOTA_QUEUE_CONNECTION=database). Production: keep redis + phpredis + queue worker.',
                ]);

                return $candidate;
            }
        }

        Log::warning("{$context}: no usable queue connection, using sync", [
            'from' => $preferred,
        ]);

        return 'sync';
    }

    public static function forWhacenter(): string
    {
        return self::resolve(
            (string) config('services.whacenter.queue_connection', 'redis'),
            'Whacenter'
        );
    }

    public static function forMoota(): string
    {
        return self::resolve(
            (string) config('services.moota.queue_connection', 'redis'),
            'Moota'
        );
    }

    public static function connectionUsable(string $name): bool
    {
        $connections = (array) config('queue.connections', []);
        if ($name !== 'sync' && ! array_key_exists($name, $connections)) {
            return false;
        }

        $driver = (string) config("queue.connections.{$name}.driver", $name);

        if ($driver !== 'redis') {
            return true;
        }

        return self::redisClientAvailable();
    }

    public static function redisClientAvailable(): bool
    {
        $client = (string) config('database.redis.client', env('REDIS_CLIENT', 'phpredis'));

        if ($client === 'predis') {
            return class_exists(\Predis\Client::class);
        }

        // phpredis: wajib ekstensi terpasang (jangan andalkan class_exists/stubs saja).
        return extension_loaded('redis');
    }
}
