<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat bracket/package yang masih memiliki pendaftar coba dihapus.
 * Menghapusnya akan ikut menghapus registrasi, order, pembayaran, tiket, dan check-in (cascade).
 */
class ComponentHasRegistrationsException extends RuntimeException
{
    public function __construct(public readonly int $registrationsCount, string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('This item still has :count registrants and cannot be deleted. Transfer them to another active item first.', ['count' => $registrationsCount]));
    }
}
