<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Otomatis ubah status event: Published → Open Regist dan Open Regist → Closed Regist sesuai tanggal
Schedule::command('events:sync-status')->everyMinute();

// Draft / pending unpaid lewat expired_at → cancelled (kuota lepas otomatis di query)
Schedule::command('orders:enforce-deadlines')->everyMinute();

// Opsional: confirmed → completed setelah event selesai
Schedule::command('orders:mark-completed')->daily();
