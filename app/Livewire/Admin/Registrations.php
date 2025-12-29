<?php

namespace App\Livewire\Admin;

use App\Models\Participant;
use App\Models\Event;
use App\Models\Category;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Registrations extends Component
{
    public $eventFilter = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $searchName = '';
    
    public $events;
    public $categories = [];
    public $participants;

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        $this->loadEvents();
        $this->loadParticipants();
    }

    public function loadEvents()
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin->isSuperAdmin()) {
            $this->events = Event::select('id', 'name')->orderBy('name')->get();
        } else {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            $this->events = Event::select('id', 'name')
                ->whereIn('id', $accessibleEventIds)
                ->orderBy('name')
                ->get();
        }
    }

    public function updatedEventFilter()
    {
        $this->loadCategories();
        $this->loadParticipants();
    }

    public function loadCategories()
    {
        if ($this->eventFilter) {
            $this->categories = Category::select('id', 'name')
                ->where('event_id', $this->eventFilter)
                ->orderBy('name')
                ->get();
        } else {
            $this->categories = [];
        }
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['categoryFilter', 'statusFilter', 'searchName'])) {
            $this->loadParticipants();
        }
    }

    public function loadParticipants()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = Participant::select(
            'participants.id',
            'participants.package_id',
            'participants.category_id',
            'participants.name',
            'participants.email',
            'participants.phone',
            'participants.registration_number',
            'participants.unique_code',
            'participants.status',
            'participants.created_at'
        )
        ->with(['package.event', 'category.event']);

        // Filter by admin access
        if (!$admin->isSuperAdmin()) {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            $query->where(function($q) use ($accessibleEventIds) {
                $q->whereHas('category.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                })
                ->orWhereHas('package.event', function ($query) use ($accessibleEventIds) {
                    $query->whereIn('id', $accessibleEventIds);
                });
            });
        }

        // Filter by event
        if ($this->eventFilter) {
            $query->whereHas('category', function ($q) {
                $q->where('event_id', $this->eventFilter);
            });
        }

        // Filter by category
        if ($this->categoryFilter) {
            $query->where('participants.category_id', $this->categoryFilter);
        }

        // Filter by status
        if ($this->statusFilter) {
            $query->where('participants.status', $this->statusFilter);
        }

        // Search by name
        if ($this->searchName) {
            $query->where('participants.name', 'like', '%' . $this->searchName . '%');
        }

        $this->participants = $query->orderBy('participants.created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.admin.registrations');
    }
}

