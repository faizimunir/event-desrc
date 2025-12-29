<?php

namespace App\Livewire\Admin;

use App\Models\Package;
use App\Models\Category;
use App\Models\Event;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PackageManagement extends Component
{
    public $packages;
    public $events;
    
    public $eventFilter = '';
    
    public $showModal = false;
    public $editingId = null;
    
    // Form fields
    public $event_id = '';
    public $name = '';
    public $description = '';
    public $price = '';
    public $status = 'active';

    protected $rules = [
        'event_id' => 'required|exists:events,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'status' => 'required|in:active,inactive,sold_out',
    ];

    public function mount()
    {
        $this->loadEvents();
        $this->loadPackages();
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
        $this->loadPackages();
        // Reset event_id in form when event filter changes
        if ($this->eventFilter) {
            $this->event_id = $this->eventFilter;
        }
    }

    public function loadPackages()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = Package::select(
            'packages.id',
            'packages.event_id',
            'packages.name',
            'packages.description',
            'packages.price',
            'packages.status',
            'packages.created_at',
            'packages.updated_at'
        )
        ->with(['event']);

        // Filter by admin access
        if (!$admin->isSuperAdmin()) {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            $query->whereIn('packages.event_id', $accessibleEventIds);
        }

        // Filter by event
        if ($this->eventFilter) {
            $query->where('packages.event_id', $this->eventFilter);
        }

        $this->packages = $query->orderBy('packages.created_at', 'desc')->get();
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        $this->resetValidation();
        
        if ($id) {
            $this->editingId = $id;
            $package = Package::with(['event'])->findOrFail($id);
            $this->event_id = $package->event_id;
            $this->name = $package->name;
            $this->description = $package->description;
            $this->price = $package->price;
            $this->status = $package->status;
            
            // Set event filter
            $this->eventFilter = $package->event_id;
        } else {
            // If event filter is set, use it for new package
            if ($this->eventFilter) {
                $this->event_id = $this->eventFilter;
            } else {
                // Jika tidak ada event filter, set event_id ke empty string
                $this->event_id = '';
                session()->flash('error', 'Silakan pilih event terlebih dahulu di filter sebelum membuat paket baru.');
            }
        }
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->event_id = '';
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->status = 'active';
        $this->resetErrorBag();
        
        // Don't reset eventFilter here to maintain filter state
        // But set event_id from filter if available
        if ($this->eventFilter) {
            $this->event_id = $this->eventFilter;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'max_participants' => null, // Kuota diatur di kategori, bukan di paket
            'status' => $this->status,
        ];

        if ($this->editingId) {
            Package::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Paket berhasil diupdate.');
        } else {
            Package::create($data);
            session()->flash('success', 'Paket berhasil dibuat.');
        }

        $this->closeModal();
        $this->loadPackages();
    }

    public function delete($id)
    {
        Package::findOrFail($id)->delete();
        session()->flash('success', 'Paket berhasil dihapus.');
        $this->loadPackages();
    }

    public function render()
    {
        return view('livewire.admin.package-management');
    }
}

