<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class EventDetail extends Component
{
    public $eventId;
    public $event;
    public $categories;
    public $selectedCategoryId = null;
    public $selectedCategory = null;

    public function mount($id)
    {
        $this->eventId = $id;
        $this->loadEvent();
    }

    public function loadEvent()
    {
        // Cache event detail for 30 minutes
        $cacheKey = "event_detail_{$this->eventId}";
        $this->event = Cache::remember($cacheKey, 1800, function () {
            return Event::select('id', 'name', 'description', 'start_date', 'end_date', 'registration_start', 'registration_end', 'location', 'image', 'status', 'created_at', 'updated_at')
                ->with([
                    'categories' => function ($query) {
                        $query->select('id', 'event_id', 'name', 'description', 'max_participants', 'status')
                            ->where('status', 'active');
                    },
                    'activePackages' => function ($q) {
                        $q->select('id', 'event_id', 'name', 'description', 'price', 'status');
                    }
                ])
                ->findOrFail($this->eventId);
        });

        $this->categories = $this->event->categories;
    }

    public function selectCategory($categoryId)
    {
        try {
            $this->selectedCategoryId = (int)$categoryId;
            $this->selectedCategory = Category::findOrFail($this->selectedCategoryId);
            
            // Load packages from event (packages tersedia untuk semua category di event yang sama)
            // Package dibuat sekali per event dan otomatis tersedia untuk semua category
            $this->selectedCategory->packages = \App\Models\Package::where('event_id', $this->selectedCategory->event_id)
                ->where('status', 'active')
                ->orderBy('price', 'asc')
                ->get();

            $this->dispatch('category-selected');
        } catch (\Exception $e) {
            session()->flash('error', 'Kategori tidak ditemukan.');
        }
    }

    public function clearSelection()
    {
        $this->selectedCategoryId = null;
        $this->selectedCategory = null;
    }

    public function render()
    {
        return view('livewire.event-detail');
    }
}
