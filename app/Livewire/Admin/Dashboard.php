<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function render()
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin->isSuperAdmin()) {
            $events = Event::count();
            $participants = Participant::count();
            $payments = Payment::where('status', 'paid')->count();
            $pendingPayments = Payment::where('status', 'pending')->count();
            $recentEvents = Event::select('id', 'name', 'start_date', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } else {
            $events = Event::where('created_by', $admin->id)
                ->orWhere('id', $admin->event_id)
                ->count();
            $participants = Participant::where(function($q) use ($admin) {
                $q->whereHas('category.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                })
                ->orWhereHas('package.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                });
            })->count();
            $payments = Payment::where(function($q) use ($admin) {
                $q->whereHas('participant.category.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                })
                ->orWhereHas('participant.package.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                });
            })->where('status', 'paid')->count();
            $pendingPayments = Payment::where(function($q) use ($admin) {
                $q->whereHas('participant.category.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                })
                ->orWhereHas('participant.package.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                });
            })->where('status', 'pending')->count();
            $recentEvents = Event::where('created_by', $admin->id)
                ->orWhere('id', $admin->event_id)
                ->select('id', 'name', 'start_date', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        return view('livewire.admin.dashboard', [
            'events' => $events,
            'participants' => $participants,
            'payments' => $payments,
            'pendingPayments' => $pendingPayments,
            'recentEvents' => $recentEvents,
        ]);
    }
}

