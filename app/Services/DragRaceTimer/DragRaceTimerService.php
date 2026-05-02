<?php

namespace App\Services\DragRaceTimer;

use App\Events\DragRaceTimer\LaneFinished;
use App\Events\DragRaceTimer\RaceHistoryUpdated;
use App\Events\DragRaceTimer\RaceReset;
use App\Events\DragRaceTimer\RaceStarted;
use Illuminate\Support\Facades\Cache;

class DragRaceTimerService
{
    public const CACHE_KEY = 'drag_race_timer:state:v1';

    public const HISTORY_CACHE_KEY = 'drag_race_timer:history:v1';

    public const LOCK_KEY = 'drag_race_timer:lock';

    /**
     * @return array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null}
     */
    public function currentState(): array
    {
        return Cache::get(self::CACHE_KEY, $this->idleState());
    }

    /**
     * Race state plus shared race log (newest first).
     *
     * @return array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null, history: list<array<string, int>>}
     */
    public function stateWithHistory(): array
    {
        return array_merge($this->currentState(), [
            'history' => $this->history(),
        ]);
    }

    /**
     * Shared log across devices (no entry cap; stored in cache).
     *
     * @return list<array{start_time_ms: int, finish_a_ms: int, finish_b_ms: int, recorded_at_ms: int}>
     */
    public function history(): array
    {
        $raw = Cache::get(self::HISTORY_CACHE_KEY, []);

        return is_array($raw) ? $raw : [];
    }

    public function clearHistory(): void
    {
        Cache::forget(self::HISTORY_CACHE_KEY);
        broadcast(new RaceHistoryUpdated([]));
    }

    /**
     * @return array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null}
     */
    public function idleState(): array
    {
        return [
            'phase' => 'idle',
            'start_time_ms' => null,
            'finish_a_ms' => null,
            'finish_b_ms' => null,
        ];
    }

    /**
     * @return array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null}
     */
    public function start(bool $withCountdown): array
    {
        return Cache::lock(self::LOCK_KEY, 5)->block(3, function () use ($withCountdown) {
            $state = Cache::get(self::CACHE_KEY, $this->idleState());
            if ($state['phase'] !== 'idle') {
                abort(409, __('Race is not idle. Reset before starting a new race.'));
            }

            $nowMs = $this->nowMs();
            $countdownMs = $withCountdown ? 3000 : 0;
            $startTimeMs = $nowMs + $countdownMs;

            $new = [
                'phase' => 'running',
                'start_time_ms' => $startTimeMs,
                'finish_a_ms' => null,
                'finish_b_ms' => null,
            ];
            Cache::put(self::CACHE_KEY, $new, now()->addHours(8));

            broadcast(new RaceStarted($startTimeMs, $countdownMs))->toOthers();

            return $new;
        });
    }

    /**
     * @return array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null}
     */
    public function stopLane(string $lane): array
    {
        if (! in_array($lane, ['a', 'b'], true)) {
            abort(400, __('Invalid lane.'));
        }

        return Cache::lock(self::LOCK_KEY, 5)->block(3, function () use ($lane) {
            $state = Cache::get(self::CACHE_KEY, $this->idleState());
            if ($state['phase'] !== 'running') {
                abort(409, __('Cannot stop lane: race is not running.'));
            }

            $key = $lane === 'a' ? 'finish_a_ms' : 'finish_b_ms';
            if ($state[$key] !== null) {
                abort(422, __('This lane has already finished.'));
            }

            $finishMs = $this->nowMs();
            $state[$key] = $finishMs;

            if ($state['finish_a_ms'] !== null && $state['finish_b_ms'] !== null) {
                $state['phase'] = 'finished';
            }

            Cache::put(self::CACHE_KEY, $state, now()->addHours(8));
            broadcast(new LaneFinished($lane, $finishMs))->toOthers();

            if ($state['phase'] === 'finished') {
                $this->appendRaceToHistory($state);
            }

            return $state;
        });
    }

    /**
     * @return array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null}
     */
    public function reset(): array
    {
        return Cache::lock(self::LOCK_KEY, 5)->block(3, function () {
            Cache::forget(self::CACHE_KEY);
            broadcast(new RaceReset)->toOthers();

            return $this->idleState();
        });
    }

    /**
     * @param  array{phase: string, start_time_ms: int|null, finish_a_ms: int|null, finish_b_ms: int|null}  $state
     */
    private function appendRaceToHistory(array $state): void
    {
        if ($state['start_time_ms'] === null || $state['finish_a_ms'] === null || $state['finish_b_ms'] === null) {
            return;
        }

        $entry = [
            'start_time_ms' => (int) $state['start_time_ms'],
            'finish_a_ms' => (int) $state['finish_a_ms'],
            'finish_b_ms' => (int) $state['finish_b_ms'],
            'recorded_at_ms' => $this->nowMs(),
        ];

        $history = $this->history();
        array_unshift($history, $entry);

        Cache::put(self::HISTORY_CACHE_KEY, $history, now()->addDays(365));

        broadcast(new RaceHistoryUpdated($history));
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
