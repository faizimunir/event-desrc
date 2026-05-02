<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('drag-race-timer', function (\App\Models\User $user) {
    return $user->canAs('access_drag_race_timer');
});
