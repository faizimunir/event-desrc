<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Rundown;
use Carbon\Carbon;
use Tests\TestCase;

class RundownTimingStatusTest extends TestCase
{
    public function test_ontime_when_actual_within_schedule(): void
    {
        $event = new Event([
            'start_at' => Carbon::parse('2026-08-16 08:00:00'),
            'end_at' => Carbon::parse('2026-08-16 18:00:00'),
        ]);

        $rundown = new Rundown([
            'start_time' => '10:00:00',
            'end_time' => '10:50:00',
            'actual_started_at' => Carbon::parse('2026-08-16 10:00:00'),
            'actual_ended_at' => Carbon::parse('2026-08-16 10:50:00'),
        ]);
        $rundown->setRelation('event', $event);

        $this->assertSame(Rundown::TIMING_ONTIME, $rundown->timingStatus());
    }

    public function test_delayed_when_actual_start_late(): void
    {
        $event = new Event([
            'start_at' => Carbon::parse('2026-08-16 08:00:00'),
            'end_at' => Carbon::parse('2026-08-16 18:00:00'),
        ]);

        $rundown = new Rundown([
            'start_time' => '10:00:00',
            'end_time' => '10:50:00',
            'actual_started_at' => Carbon::parse('2026-08-16 10:05:00'),
            'actual_ended_at' => Carbon::parse('2026-08-16 10:50:00'),
        ]);
        $rundown->setRelation('event', $event);

        $this->assertSame(Rundown::TIMING_DELAYED, $rundown->timingStatus());
    }

    public function test_live_while_playing(): void
    {
        $rundown = new Rundown([
            'start_time' => '10:00:00',
            'end_time' => '10:50:00',
            'actual_started_at' => Carbon::parse('2026-08-16 10:00:00'),
            'actual_ended_at' => null,
        ]);

        $this->assertSame(Rundown::TIMING_LIVE, $rundown->timingStatus());
        $this->assertTrue($rundown->isPlaying());
    }
}
