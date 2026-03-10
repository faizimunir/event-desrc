<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class SyncEventStatusCommand extends Command
{
    protected $signature = 'events:sync-status';

    protected $description = 'Sync event status based on registration_opens_at and registration_closes_at';

    public function handle(): int
    {
        $now = now();

        // Published → Open Regist when registration_opens_at has passed
        $opened = Event::query()
            ->where('status', Event::STATUS_PUBLISHED)
            ->whereNotNull('registration_opens_at')
            ->where('registration_opens_at', '<=', $now)
            ->update(['status' => Event::STATUS_OPEN_REGIST]);

        // Open Regist → Closed Regist when registration_closes_at has passed
        $closed = Event::query()
            ->where('status', Event::STATUS_OPEN_REGIST)
            ->whereNotNull('registration_closes_at')
            ->where('registration_closes_at', '<=', $now)
            ->update(['status' => Event::STATUS_CLOSED_REGIST]);

        if ($opened > 0 || $closed > 0) {
            $this->info("Synced: {$opened} event(s) opened for registration, {$closed} event(s) closed.");
        }

        return self::SUCCESS;
    }
}
