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
            $recentEvents = Event::select('id', 'name', 'start_date', 'is_coming_soon', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } else {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            
            $events = Event::whereIn('id', $accessibleEventIds)->count();
            
            $participants = Participant::where(function($q) use ($admin, $accessibleEventIds) {
                $q->whereHas('category.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                })
                ->orWhereHas('package.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                });
            })->count();
            
            $payments = Payment::where(function($q) use ($accessibleEventIds) {
                $q->whereHas('participant.category.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                })
                ->orWhereHas('participant.package.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                });
            })->where('status', 'paid')->count();
            
            $pendingPayments = Payment::where(function($q) use ($accessibleEventIds) {
                $q->whereHas('participant.category.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                })
                ->orWhereHas('participant.package.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                });
            })->where('status', 'pending')->count();
            
            $recentEvents = Event::whereIn('id', $accessibleEventIds)
                ->select('id', 'name', 'start_date', 'is_coming_soon', 'status', 'created_at')
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

