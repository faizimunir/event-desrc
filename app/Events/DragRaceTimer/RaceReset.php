<?php

namespace App\Events\DragRaceTimer;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RaceReset implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('drag-race-timer')];
    }

    public function broadcastAs(): string
    {
        return 'RaceReset';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
