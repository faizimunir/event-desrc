<?php

namespace Database\Seeders;

use App\Models\Bracket;
use App\Models\Event;
use App\Models\Location;
use App\Models\MasterOfCeremony;
use App\Models\Organizer;
use App\Models\RacingCommittee;
use Illuminate\Database\Seeder;

class DummyTestSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::firstOrCreate(['name' => 'MPP']);

        $mc = MasterOfCeremony::firstOrCreate(['name' => 'Om Dodit']);

        $organizer = Organizer::firstOrCreate(['name' => 'Bhinneka']);

        $rc = RacingCommittee::firstOrCreate(['name' => 'DRC']);

        $event = Event::firstOrCreate(
            ['title' => 'Bhinneka'],
            [
                'category' => Event::CATEGORY_TAHUN,
                'status' => Event::STATUS_DRAFT,
                'start_at' => now()->addDays(7)->startOfDay(),
                'end_at' => now()->addDays(7)->endOfDay(),
                'organizer_id' => $organizer->id,
                'racing_committee_id' => $rc->id,
                'master_of_ceremony_id' => $mc->id,
                'location_id' => $location->id,
            ]
        );

        // Keep relations in sync if the event already existed.
        $event->forceFill([
            'category' => Event::CATEGORY_TAHUN,
            'organizer_id' => $organizer->id,
            'racing_committee_id' => $rc->id,
            'master_of_ceremony_id' => $mc->id,
            'location_id' => $location->id,
        ])->save();

        Bracket::firstOrCreate(
            ['event_id' => $event->id, 'name' => '2019 Boys'],
            [
                'gender_rule' => Bracket::GENDER_BOYS,
                'rule_type' => Bracket::RULE_TYPE_BIRTH_YEAR,
                'birth_year_start' => 2019,
                'birth_year_end' => 2019,
            ]
        );

        Bracket::firstOrCreate(
            ['event_id' => $event->id, 'name' => '2019 Girls'],
            [
                'gender_rule' => Bracket::GENDER_GIRLS,
                'rule_type' => Bracket::RULE_TYPE_BIRTH_YEAR,
                'birth_year_start' => 2019,
                'birth_year_end' => 2019,
            ]
        );
    }
}

