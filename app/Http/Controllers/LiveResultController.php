<?php

namespace App\Http\Controllers;

use App\Models\Event;

class LiveResultController extends Controller
{
    public function index()
    {
        return view('live-result.index');
    }

    public function show(Event $event)
    {
        if ($event->isDraft() || ! $event->has_live_result) {
            abort(404);
        }

        $event->load('location');

        return view('live-result.show', compact('event'));
    }
}
