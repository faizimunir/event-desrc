<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Otomatis ubah status event: Published → Open Regist dan Open Regist → Closed Regist sesuai tanggal
Schedule::command('events:sync-status')->everyMinute();

// Batalkan order yang sudah lewat expired_at (15 menit), lepaskan slot bracket/package
Schedule::command('orders:expire-pending')->everyMinute();
