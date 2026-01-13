<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EventManagement extends Component
{
    use WithFileUploads;

    public $events;
    public $showModal = false;
    public $editingId = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $is_coming_soon = false;
    public $registration_start = '';
    public $registration_end = '';
    public $is_registration_coming_soon = false;
    public $registration_open = false;
    public $location = '';
    public $image;
    public $logo;
    public $logoPreview = null;
    public $status = 'draft';
    public $payment_method = 'manual';

    public function getRules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status' => 'required|in:draft,published,closed,cancelled',
            'payment_method' => 'required|in:manual,moota',
            'is_coming_soon' => 'boolean',
            'is_registration_coming_soon' => 'boolean',
        ];

        // Date fields are required only if coming soon is not checked
        if (!$this->is_coming_soon) {
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
        } else {
            $rules['start_date'] = 'nullable|date';
            $rules['end_date'] = 'nullable|date';
            // Only validate after_or_equal if both dates are provided
            if ($this->start_date && $this->end_date) {
                $rules['end_date'] = 'nullable|date|after_or_equal:start_date';
            }
        }

        if (!$this->is_registration_coming_soon) {
            $rules['registration_start'] = 'required|date';
            $rules['registration_end'] = 'required|date|after_or_equal:registration_start';
        } else {
            $rules['registration_start'] = 'nullable|date';
            $rules['registration_end'] = 'nullable|date';
            // Only validate after_or_equal if both dates are provided
            if ($this->registration_start && $this->registration_end) {
                $rules['registration_end'] = 'nullable|date|after_or_equal:registration_start';
            }
        }

        return $rules;
    }

    public function mount()
    {
        $this->loadEvents();
    }

    public function loadEvents()
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin->isSuperAdmin()) {
            $this->events = Event::select('id', 'name', 'start_date', 'location', 'status', 'registration_open', 'payment_method', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Get accessible event IDs (created events, event_id, or from pivot table)
            $accessibleEventIds = $admin->getAccessibleEventIds();
            
            $this->events = Event::select('id', 'name', 'start_date', 'location', 'status', 'registration_open', 'payment_method', 'created_at')
                ->whereIn('id', $accessibleEventIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        $this->editingId = $id;
        
        if ($id) {
            $event = Event::findOrFail($id);
            $this->name = $event->name;
            $this->description = $event->description;
            $this->start_date = $event->start_date ? $event->start_date->format('Y-m-d') : '';
            $this->end_date = $event->end_date ? $event->end_date->format('Y-m-d') : '';
            $this->is_coming_soon = $event->is_coming_soon ?? false;
            // Convert to WIB timezone for display
            $this->registration_start = $event->registration_start ? $event->registration_start->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i') : '';
            $this->registration_end = $event->registration_end ? $event->registration_end->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i') : '';
            $this->is_registration_coming_soon = $event->is_registration_coming_soon ?? false;
            $this->registration_open = $event->registration_open ?? false;
            $this->location = $event->location;
            $this->status = $event->status;
            $this->payment_method = $event->payment_method ?? 'manual';
            // Set logo preview if logo exists
            if ($event->logo_url) {
                $this->logoPreview = asset('storage/' . $event->logo_url);
            } else {
                $this->logoPreview = null;
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
        $this->name = '';
        $this->description = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->is_coming_soon = false;
        $this->registration_start = '';
        $this->registration_end = '';
        $this->is_registration_coming_soon = false;
        $this->registration_open = false;
        $this->location = '';
        $this->image = null;
        $this->logo = null;
        $this->logoPreview = null;
        $this->status = 'draft';
        $this->payment_method = 'manual';
        $this->resetErrorBag();
    }

    public function save()
    {
        $admin = Auth::guard('admin')->user();
        
        // Restrict Co Admin Event from creating/editing events
        if ($admin->isCoAdminEvent()) {
            session()->flash('error', 'Co Admin Event tidak dapat membuat atau mengedit event.');
            return;
        }
        
        // If editing, check if admin has access to this event
        if ($this->editingId) {
            if ($admin->isAdminEvent() && !$admin->canAccessEvent($this->editingId)) {
                session()->flash('error', 'Anda tidak memiliki akses untuk mengedit event ini.');
                return;
            }
        }
        
        $this->validate($this->getRules());

        // Store editingId before any operations
        $editingId = $this->editingId;

        // Convert datetime-local input to Carbon with WIB timezone
        // Input dari datetime-local sudah dalam format local time (WIB dari browser)
        // Parse sebagai WIB, lalu simpan (dengan timezone Asia/Jakarta, Laravel akan handle storage)
        $registrationStart = null;
        $registrationEnd = null;
        
        if ($this->registration_start && !$this->is_registration_coming_soon) {
            $registrationStart = Carbon::createFromFormat('Y-m-d\TH:i', $this->registration_start, 'Asia/Jakarta');
            $registrationStart->setTimezone('Asia/Jakarta');
        }
        
        if ($this->registration_end && !$this->is_registration_coming_soon) {
            $registrationEnd = Carbon::createFromFormat('Y-m-d\TH:i', $this->registration_end, 'Asia/Jakarta');
            $registrationEnd->setTimezone('Asia/Jakarta');
        }
        
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->is_coming_soon ? null : $this->start_date,
            'end_date' => $this->is_coming_soon ? null : $this->end_date,
            'is_coming_soon' => $this->is_coming_soon,
            'registration_start' => $registrationStart,
            'registration_end' => $registrationEnd,
            'is_registration_coming_soon' => $this->is_registration_coming_soon,
            'registration_open' => $this->registration_open,
            'location' => $this->location,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
        ];

        // Only set created_by for new events
        if (!$editingId) {
            $data['created_by'] = Auth::guard('admin')->id();
        }

        if ($this->image) {
            if ($editingId) {
                $event = Event::findOrFail($editingId);
                if ($event->image) {
                    Storage::disk('public')->delete($event->image);
                }
            }
            $data['image'] = $this->image->store('events', 'public');
        }

        if ($this->logo) {
            if ($editingId) {
                $event = Event::findOrFail($editingId);
                if ($event->logo_url) {
                    Storage::disk('public')->delete($event->logo_url);
                }
            }
            $data['logo_url'] = $this->logo->store('logos', 'public');
        }

        if ($editingId) {
            $event = Event::findOrFail($editingId);
            $event->update($data);
            session()->flash('success', 'Event berhasil diupdate.');
        } else {
            Event::create($data);
            session()->flash('success', 'Event berhasil dibuat.');
        }

        $this->closeModal();
        $this->loadEvents();
    }

    public function delete($id)
    {
        $admin = Auth::guard('admin')->user();
        
        // Only SuperAdmin can delete events
        if (!$admin->isSuperAdmin()) {
            session()->flash('error', 'Hanya Super Admin yang dapat menghapus event. Silakan hubungi Super Admin atau Administrator utama untuk menghapus event.');
            return;
        }
        
        $event = Event::findOrFail($id);
        
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }
        if ($event->logo_url) {
            Storage::disk('public')->delete($event->logo_url);
        }
        $event->delete();
        session()->flash('success', 'Event berhasil dihapus.');
        $this->loadEvents();
    }

    public function removeLogo()
    {
        if ($this->editingId) {
            $event = Event::findOrFail($this->editingId);
            if ($event->logo_url) {
                Storage::disk('public')->delete($event->logo_url);
                $event->logo_url = null;
                $event->save();
                $this->logoPreview = null;
                session()->flash('success', 'Logo berhasil dihapus.');
            }
        }
        $this->logo = null;
        $this->logoPreview = null;
    }

    public function updatedLogo()
    {
        $this->validateOnly('logo');
        if ($this->logo) {
            $this->logoPreview = $this->logo->temporaryUrl();
        }
    }

    public function toggleRegistration($id)
    {
        $admin = Auth::guard('admin')->user();
        
        // Only SuperAdmin and Admin Event can toggle registration
        if ($admin->isCoAdminEvent()) {
            session()->flash('error', 'Co Admin Event tidak dapat mengubah status pendaftaran.');
            return;
        }
        
        // Admin Event can only toggle registration for events they have access to
        if ($admin->isAdminEvent() && !$admin->canAccessEvent($id)) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengubah status pendaftaran event ini.');
            return;
        }
        
        $event = Event::findOrFail($id);
        $event->registration_open = !$event->registration_open;
        $event->save();
        
        $status = $event->registration_open ? 'dibuka' : 'ditutup';
        session()->flash('success', "Pendaftaran event '{$event->name}' berhasil {$status}.");
        $this->loadEvents();
    }

    public function render()
    {
        return view('livewire.admin.event-management');
    }
}

