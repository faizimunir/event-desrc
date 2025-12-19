<?php

namespace App\Livewire\Admin;

use App\Models\PaymentSetting;
use App\Models\Event;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PaymentSettings extends Component
{
    public $settings;
    public $events;
    public $showModal = false;
    public $editingId = null;
    
    public $event_id = null;
    public $bank_name = '';
    public $account_number = '';
    public $account_name = '';
    public $is_default = false;
    public $status = 'active';

    protected $rules = [
        'event_id' => 'nullable|exists:events,id',
        'bank_name' => 'required|string|max:255',
        'account_number' => 'required|string|max:255',
        'account_name' => 'required|string|max:255',
        'is_default' => 'boolean',
        'status' => 'required|in:active,inactive',
    ];

    public function mount()
    {
        $this->loadSettings();
        $this->loadEvents();
    }

    public function loadSettings()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = PaymentSetting::with('event');

        if (!$admin->isSuperAdmin()) {
            $query->whereHas('event', function ($q) use ($admin) {
                $q->where('created_by', $admin->id)
                  ->orWhere('id', $admin->event_id);
            })->orWhereNull('event_id');
        }

        $this->settings = $query->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();
    }

    public function loadEvents()
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin->isSuperAdmin()) {
            $this->events = Event::select('id', 'name')->orderBy('name')->get();
        } else {
            $this->events = Event::select('id', 'name')
                ->where('created_by', $admin->id)
                ->orWhere('id', $admin->event_id)
                ->orderBy('name')
                ->get();
        }
    }

    public function openModal($id = null)
    {
        $this->editingId = $id;
        $this->resetForm();
        
        if ($id) {
            $setting = PaymentSetting::findOrFail($id);
            $this->event_id = $setting->event_id;
            $this->bank_name = $setting->bank_name;
            $this->account_number = $setting->account_number;
            $this->account_name = $setting->account_name;
            $this->is_default = $setting->is_default;
            $this->status = $setting->status;
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
        $this->event_id = null;
        $this->bank_name = '';
        $this->account_number = '';
        $this->account_name = '';
        $this->is_default = false;
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        // If setting as default, unset other defaults
        if ($this->is_default) {
            PaymentSetting::where('is_default', true)->update(['is_default' => false]);
        }

        $data = [
            'event_id' => $this->event_id ?: null,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'is_default' => $this->is_default,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            PaymentSetting::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Pengaturan pembayaran berhasil diupdate.');
        } else {
            PaymentSetting::create($data);
            session()->flash('success', 'Pengaturan pembayaran berhasil dibuat.');
        }

        $this->closeModal();
        $this->loadSettings();
    }

    public function delete($id)
    {
        PaymentSetting::findOrFail($id)->delete();
        session()->flash('success', 'Pengaturan pembayaran berhasil dihapus.');
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.admin.payment-settings');
    }
}

