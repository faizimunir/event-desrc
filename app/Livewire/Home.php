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
            return Event::select('id', 'name', 'description', 'start_date', 'end_date', 'is_coming_soon', 'location', 'image', 'status', 'registration_start', 'registration_end', 'is_registration_coming_soon', 'created_at', 'updated_at')
                ->where('status', 'published')
                ->orderByRaw('COALESCE(start_date, "9999-12-31") DESC')
                ->get()
                ->map(function ($event) {
                    // Use WIB timezone explicitly
                    $now = now('Asia/Jakarta');
                    
                    // Handle registration dates for coming soon events
                    if ($event->is_registration_coming_soon || !$event->registration_start || !$event->registration_end) {
                        $event->is_open = false;
                        $event->is_closed = false;
                    } else {
                        $registrationStart = \Carbon\Carbon::parse($event->registration_start)->setTimezone('Asia/Jakarta');
                        $registrationEnd = \Carbon\Carbon::parse($event->registration_end)->setTimezone('Asia/Jakarta');
                        
                        $event->is_open = $now >= $registrationStart && $now <= $registrationEnd;
                        $event->is_closed = $now > $registrationEnd || $event->status === 'closed';
                    }
                    
                    return $event;
                });
        });

        return view('livewire.home', [
            'events' => $events,
        ]);
    }
}
