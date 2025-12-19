<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        // Cache events for 1 hour
        $events = Cache::remember('published_events', 3600, function () {
            return Event::select('id', 'name', 'description', 'start_date', 'end_date', 'location', 'image', 'status', 'registration_start', 'registration_end', 'created_at', 'updated_at')
                ->where('status', 'published')
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($event) {
                    // Use WIB timezone explicitly
                    $now = now('Asia/Jakarta');
                    $registrationStart = \Carbon\Carbon::parse($event->registration_start)->setTimezone('Asia/Jakarta');
                    $registrationEnd = \Carbon\Carbon::parse($event->registration_end)->setTimezone('Asia/Jakarta');
                    
                    $event->is_open = $now >= $registrationStart && $now <= $registrationEnd;
                    $event->is_closed = $now > $registrationEnd || $event->status === 'closed';
                    return $event;
                });
        });

        return view('livewire.home', [
            'events' => $events,
        ]);
    }
}
