<?php

use App\Support\QueueConnection;

test('whacenter queue falls back when redis extension is missing', function () {
    config([
        'services.whacenter.queue_connection' => 'redis',
        'queue.default' => 'database',
        'queue.connections.redis.driver' => 'redis',
        'queue.connections.database.driver' => 'database',
        'database.redis.client' => 'phpredis',
    ]);

    if (extension_loaded('redis')) {
        expect(QueueConnection::forWhacenter())->toBe('redis');

        return;
    }

    expect(QueueConnection::redisClientAvailable())->toBeFalse()
        ->and(QueueConnection::forWhacenter())->toBe('database')
        ->and(QueueConnection::forMoota())->toBe('database');
});
