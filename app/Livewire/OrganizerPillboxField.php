<?php

namespace App\Livewire;

use App\Models\Organizer;
use Livewire\Component;

class OrganizerPillboxField extends Component
{
    public string $organizerSearch = '';

    /** @var array<int, int> */
    public array $selectedOrganizerIds = [];

    public function getOrganizersProperty()
    {
        $query = Organizer::query()->orderBy('name');

        if (trim($this->organizerSearch) !== '') {
            $query->where('name', 'like', '%'.trim($this->organizerSearch).'%')->limit(20);

            return $query->get();
        }

        if (count($this->selectedOrganizerIds) > 0) {
            $selected = Organizer::whereIn('id', $this->selectedOrganizerIds)->orderBy('name')->get();
            $others = Organizer::whereNotIn('id', $this->selectedOrganizerIds)->orderBy('name')->limit(15)->get();

            return $selected->merge($others)->unique('id')->values();
        }

        return $query->limit(20)->get();
    }

    public function createOrganizer(): void
    {
        $name = trim($this->organizerSearch);
        if ($name === '') {
            return;
        }

        $organizer = Organizer::firstOrCreate(
            ['name' => $name],
            ['name' => $name]
        );

        if (! in_array($organizer->id, $this->selectedOrganizerIds, true)) {
            $this->selectedOrganizerIds[] = $organizer->id;
        }

        $this->organizerSearch = '';
    }

    public function render()
    {
        return view('livewire.organizer-pillbox-field');
    }
}
