<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Component;

class TeamPillboxField extends Component
{
    public string $teamSearch = '';

    /** @var array<int, int> */
    public array $selectedTeamIds = [];

    public ?int $organizerId = null;

    public string $fieldLabel = '';

    public function mount(
        ?int $organizerId = null,
        array $initialTeamIds = [],
        ?string $fieldLabel = null,
    ): void {
        $this->organizerId = $organizerId;
        $this->fieldLabel = $fieldLabel ?? __('Community / Team / Sponsor');
        $this->selectedTeamIds = array_values(array_unique(array_filter(
            array_map('intval', $initialTeamIds),
            fn (int $id) => $id > 0
        )));
    }

    public function getTeamsProperty()
    {
        $query = Team::query()->orderBy('name');

        if ($this->organizerId !== null) {
            $query->where('organizer_id', $this->organizerId);
        }

        if (trim($this->teamSearch) !== '') {
            $query->where('name', 'like', '%'.trim($this->teamSearch).'%')->limit(20);

            return $query->get();
        }

        if (count($this->selectedTeamIds) > 0) {
            $selected = Team::whereIn('id', $this->selectedTeamIds)->orderBy('name')->get();
            $others = Team::whereNotIn('id', $this->selectedTeamIds)
                ->when($this->organizerId !== null, fn ($q) => $q->where('organizer_id', $this->organizerId))
                ->orderBy('name')
                ->limit(15)
                ->get();

            return $selected->merge($others)->unique('id')->values();
        }

        return $query->limit(20)->get();
    }

    public function createTeam(): void
    {
        $name = trim($this->teamSearch);
        if ($name === '') {
            return;
        }

        $team = Team::firstOrCreate(
            [
                'name' => $name,
                'organizer_id' => $this->organizerId,
            ],
            [
                'name' => $name,
                'organizer_id' => $this->organizerId,
                'type' => $this->organizerId !== null ? 'team' : null,
            ]
        );

        if (! in_array($team->id, $this->selectedTeamIds, true)) {
            $this->selectedTeamIds[] = $team->id;
        }

        $this->teamSearch = '';
    }

    public function render()
    {
        return view('livewire.team-pillbox-field');
    }
}
