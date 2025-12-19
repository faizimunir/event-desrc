<?php

namespace App\Livewire\Admin;

use App\Models\Admin;
use App\Models\Event;
use App\Models\EventAdminFee;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SystemManagement extends Component
{
    public $activeTab = 'admins'; // 'admins' or 'fees'
    
    // Admin Management
    public $admins;
    public $events;
    public $showAdminModal = false;
    public $editingAdminId = null;
    public $adminName = '';
    public $adminEmail = '';
    public $adminPassword = '';
    public $adminRole = 'admin_event';
    public $adminEventId = null;
    public $adminStatus = 'active';

    // Admin Fees Management
    public $adminFees;
    public $showFeeModal = false;
    public $editingFeeId = null;
    public $feeEventId = null;
    public $feeAmount = 0;
    public $feeType = 'fixed';
    public $feePercentage = null;
    public $feeDescription = '';

    protected $rules = [
        'adminName' => 'required|string|max:255',
        'adminEmail' => 'required|email|max:255',
        'adminPassword' => 'required|string|min:8',
        'adminRole' => 'required|in:super_admin,admin_event',
        'adminEventId' => 'nullable|exists:events,id',
        'adminStatus' => 'required|in:active,inactive',
        'feeEventId' => 'required|exists:events,id',
        'feeAmount' => 'required|numeric|min:0',
        'feeType' => 'required|in:fixed,percentage',
        'feePercentage' => 'nullable|numeric|min:0|max:100',
        'feeDescription' => 'nullable|string',
    ];

    public function mount()
    {
        $this->loadAdmins();
        $this->loadEvents();
        $this->loadAdminFees();
    }

    public function loadAdmins()
    {
        $this->admins = Admin::with('event')->orderBy('created_at', 'desc')->get();
    }

    public function loadEvents()
    {
        $this->events = Event::select('id', 'name')->orderBy('name')->get();
    }

    public function loadAdminFees()
    {
        $this->adminFees = EventAdminFee::with('event')->orderBy('created_at', 'desc')->get();
    }

    public function openAdminModal($id = null)
    {
        $this->editingAdminId = $id;
        $this->resetAdminForm();
        
        if ($id) {
            $admin = Admin::findOrFail($id);
            $this->adminName = $admin->name;
            $this->adminEmail = $admin->email;
            $this->adminPassword = '';
            $this->adminRole = $admin->role;
            $this->adminEventId = $admin->event_id;
            $this->adminStatus = $admin->status;
        }
        
        $this->showAdminModal = true;
    }

    public function closeAdminModal()
    {
        $this->showAdminModal = false;
        $this->resetAdminForm();
    }

    public function resetAdminForm()
    {
        $this->editingAdminId = null;
        $this->adminName = '';
        $this->adminEmail = '';
        $this->adminPassword = '';
        $this->adminRole = 'admin_event';
        $this->adminEventId = null;
        $this->adminStatus = 'active';
        $this->resetErrorBag();
    }

    public function saveAdmin()
    {
        $rules = [
            'adminName' => 'required|string|max:255',
            'adminEmail' => 'required|email|max:255',
            'adminRole' => 'required|in:super_admin,admin_event',
            'adminEventId' => 'nullable|exists:events,id',
            'adminStatus' => 'required|in:active,inactive',
        ];

        if ($this->editingAdminId) {
            $rules['adminEmail'] = 'required|email|max:255|unique:admins,email,' . $this->editingAdminId;
            $rules['adminPassword'] = 'nullable|string|min:8';
        } else {
            $rules['adminPassword'] = 'required|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->adminName,
            'email' => $this->adminEmail,
            'role' => $this->adminRole,
            'event_id' => $this->adminRole === 'admin_event' ? $this->adminEventId : null,
            'status' => $this->adminStatus,
        ];

        if ($this->adminPassword) {
            $data['password'] = Hash::make($this->adminPassword);
        }

        if ($this->editingAdminId) {
            Admin::findOrFail($this->editingAdminId)->update($data);
            session()->flash('success', 'Admin berhasil diupdate.');
        } else {
            Admin::create($data);
            session()->flash('success', 'Admin berhasil dibuat.');
        }

        $this->closeAdminModal();
        $this->loadAdmins();
    }

    public function deleteAdmin($id)
    {
        if ($id == Auth::guard('admin')->id()) {
            session()->flash('error', 'Tidak dapat menghapus akun sendiri.');
            return;
        }
        Admin::findOrFail($id)->delete();
        session()->flash('success', 'Admin berhasil dihapus.');
        $this->loadAdmins();
    }

    public function openFeeModal($id = null)
    {
        $this->editingFeeId = $id;
        $this->resetFeeForm();
        
        if ($id) {
            $fee = EventAdminFee::findOrFail($id);
            $this->feeEventId = $fee->event_id;
            $this->feeAmount = $fee->fee_amount;
            $this->feeType = $fee->fee_type;
            $this->feePercentage = $fee->fee_percentage;
            $this->feeDescription = $fee->description;
        }
        
        $this->showFeeModal = true;
    }

    public function closeFeeModal()
    {
        $this->showFeeModal = false;
        $this->resetFeeForm();
    }

    public function resetFeeForm()
    {
        $this->editingFeeId = null;
        $this->feeEventId = null;
        $this->feeAmount = 0;
        $this->feeType = 'fixed';
        $this->feePercentage = null;
        $this->feeDescription = '';
        $this->resetErrorBag();
    }

    public function saveFee()
    {
        $this->validate([
            'feeEventId' => 'required|exists:events,id',
            'feeAmount' => 'required|numeric|min:0',
            'feeType' => 'required|in:fixed,percentage',
            'feePercentage' => 'nullable|numeric|min:0|max:100',
            'feeDescription' => 'nullable|string',
        ]);

        $data = [
            'event_id' => $this->feeEventId,
            'fee_amount' => $this->feeAmount,
            'fee_type' => $this->feeType,
            'fee_percentage' => $this->feeType === 'percentage' ? $this->feePercentage : null,
            'description' => $this->feeDescription,
        ];

        if ($this->editingFeeId) {
            EventAdminFee::findOrFail($this->editingFeeId)->update($data);
            session()->flash('success', 'Biaya admin berhasil diupdate.');
        } else {
            EventAdminFee::updateOrCreate(
                ['event_id' => $this->feeEventId],
                $data
            );
            session()->flash('success', 'Biaya admin berhasil dibuat.');
        }

        $this->closeFeeModal();
        $this->loadAdminFees();
    }

    public function deleteFee($id)
    {
        EventAdminFee::findOrFail($id)->delete();
        session()->flash('success', 'Biaya admin berhasil dihapus.');
        $this->loadAdminFees();
    }

    public function render()
    {
        return view('livewire.admin.system-management');
    }
}

