<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Event;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CategoryManagement extends Component
{
    public $categories;
    public $events;
    
    public $eventFilter = '';
    
    public $showModal = false;
    public $editingId = null;
    
    // Form fields
    public $event_id = '';
    public $name = '';
    public $description = '';
    public $max_participants = '';
    public $status = 'active';

    protected $rules = [
        'event_id' => 'required|exists:events,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'max_participants' => 'nullable|integer|min:1',
        'status' => 'required|in:active,inactive',
    ];

    protected $messages = [
        'event_id.required' => 'Event harus dipilih.',
        'event_id.exists' => 'Event yang dipilih tidak valid.',
        'name.required' => 'Nama kategori wajib diisi.',
        'name.max' => 'Nama kategori maksimal 255 karakter.',
        'status.required' => 'Status wajib dipilih.',
        'status.in' => 'Status tidak valid.',
    ];

    public function mount()
    {
        $this->loadEvents();
        $this->loadCategories();
    }

    public function loadEvents()
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin->isSuperAdmin()) {
            $this->events = Event::select('id', 'name', 'status')
                ->where('status', 'published')
                ->orderBy('name')
                ->get();
        } else {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            $this->events = Event::select('id', 'name', 'status')
                ->whereIn('id', $accessibleEventIds)
                ->where('status', 'published')
                ->orderBy('name')
                ->get();
        }
    }

    public function updatedEventFilter()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = Category::select(
            'categories.id',
            'categories.event_id',
            'categories.name',
            'categories.description',
            'categories.max_participants',
            'categories.status',
            'categories.created_at',
            'categories.updated_at'
        )
        ->with(['event' => function ($query) {
            $query->select('id', 'name');
        }]);

        // Filter by admin access
        if (!$admin->isSuperAdmin()) {
            $accessibleEventIds = $admin->getAccessibleEventIds();
            $query->whereIn('categories.event_id', $accessibleEventIds);
        }

        // Filter by event
        if ($this->eventFilter) {
            $query->where('categories.event_id', $this->eventFilter);
        }

        $this->categories = $query->orderBy('categories.created_at', 'desc')->get();
    }

    public function openModal($id = null)
    {
        // Jika menambah kategori baru, event_id harus sudah dipilih
        if (!$id && !$this->eventFilter) {
            session()->flash('error', 'Silakan pilih event terlebih dahulu.');
            return;
        }
        
        $this->resetForm();
        $this->resetValidation();
        
        if ($id) {
            $this->editingId = $id;
            $category = Category::findOrFail($id);
            $this->event_id = $category->event_id;
            $this->name = $category->name;
            $this->description = $category->description ?? '';
            $this->max_participants = $category->max_participants ?? '';
            $this->status = $category->status;
        } else {
            // Set event_id dari filter jika menambah kategori baru
            $this->event_id = $this->eventFilter;
        }
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm()
    {
        $this->editingId = null;
        // Jangan reset event_id jika sedang menambah kategori baru (biarkan dari filter)
        if (!$this->eventFilter) {
            $this->event_id = '';
        } else {
            $this->event_id = $this->eventFilter;
        }
        $this->name = '';
        $this->description = '';
        $this->max_participants = '';
        $this->status = 'active';
    }

    public function save()
    {
        $this->validate();

        $data = [
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'max_participants' => $this->max_participants ?: null,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $category->update($data);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
            Category::create($data);
            session()->flash('success', 'Kategori berhasil ditambahkan.');
        }

        $this->loadCategories();
        $this->closeModal();
    }

    public function delete($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Check if category has packages via event_id
            // Package tersedia untuk semua category di event yang sama
            $packagesCount = \App\Models\Package::where('event_id', $category->event_id)->count();
            if ($packagesCount > 0) {
                session()->flash('error', 'Kategori tidak dapat dihapus karena event masih memiliki paket. Hapus paket terlebih dahulu jika ingin menghapus kategori.');
                return;
            }
            
            $category->delete();
            session()->flash('success', 'Kategori berhasil dihapus.');
            $this->loadCategories();
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.category-management');
    }
}

