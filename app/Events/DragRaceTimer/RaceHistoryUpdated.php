<?php

namespace App\Events\DragRaceTimer;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RaceHistoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<array<string, int>>  $history
     */
    public function __construct(
        public array $history,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('drag-race-timer')];
    }

    public function broadcastAs(): string
    {
        return 'RaceHistoryUpdated';
    }

    /**
     * @return array<string, list<array<string, int>>>
     */
    public function broadcastWith(): array
    {
        return ['history' => $this->history];
    }
}
