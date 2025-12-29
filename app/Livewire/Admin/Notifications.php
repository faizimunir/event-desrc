<?php

namespace App\Livewire\Admin;

use App\Models\NotificationTemplate;
use App\Models\Event;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Notifications extends Component
{
    public $templates;
    public $events;
    public $showModal = false;
    public $editingId = null;
    
    public $event_id = null;
    public $type = 'pending';
    public $channel = 'both';
    public $subject = '';
    public $content = '';
    public $is_default = false;
    public $status = 'active';

    protected $rules = [
        'event_id' => 'nullable|exists:events,id',
        'type' => 'required|in:pending,confirmed,rejected',
        'channel' => 'required|in:email,whatsapp,both',
        'subject' => 'nullable|string|max:255',
        'content' => 'required|string',
        'is_default' => 'boolean',
        'status' => 'required|in:active,inactive',
    ];

    public function mount()
    {
        $this->loadTemplates();
        $this->loadEvents();
    }

    public function loadTemplates()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = NotificationTemplate::with('event');

        if (!$admin->isSuperAdmin()) {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            $query->where(function($q) use ($accessibleEventIds) {
                $q->whereIn('event_id', $accessibleEventIds)
                  ->orWhereNull('event_id');
            });
        }

        $this->templates = $query->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();
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

    public function openModal($id = null)
    {
        $this->editingId = $id;
        $this->resetForm();
        
        if ($id) {
            $template = NotificationTemplate::findOrFail($id);
            $this->event_id = $template->event_id;
            $this->type = $template->type;
            $this->channel = $template->channel;
            $this->subject = $template->subject;
            $this->content = $template->content;
            $this->is_default = $template->is_default;
            $this->status = $template->status;
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
        $this->type = 'pending';
        $this->channel = 'both';
        $this->subject = '';
        $this->content = '';
        $this->is_default = false;
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        // If setting as default, unset other defaults with same type
        if ($this->is_default) {
            NotificationTemplate::where('type', $this->type)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $data = [
            'event_id' => $this->event_id ?: null,
            'type' => $this->type,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'content' => $this->content,
            'is_default' => $this->is_default,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            NotificationTemplate::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Template notifikasi berhasil diupdate.');
        } else {
            NotificationTemplate::create($data);
            session()->flash('success', 'Template notifikasi berhasil dibuat.');
        }

        $this->closeModal();
        $this->loadTemplates();
    }

    public function delete($id)
    {
        NotificationTemplate::findOrFail($id)->delete();
        session()->flash('success', 'Template notifikasi berhasil dihapus.');
        $this->loadTemplates();
    }

    public function render()
    {
        return view('livewire.admin.notifications');
    }
}

