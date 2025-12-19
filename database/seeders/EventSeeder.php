<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'name' => 'Pushbike Race Challenge 2025',
            'description' => 'Event race pushbike terbesar di Indonesia dengan berbagai kategori dan hadiah menarik.',
            'start_date' => now()->addMonths(2)->format('Y-m-d'),
            'end_date' => now()->addMonths(2)->addDays(2)->format('Y-m-d'),
            'registration_start' => now()->format('Y-m-d H:i:s'),
            'registration_end' => now()->addMonths(1)->format('Y-m-d H:i:s'),
            'location' => 'Jakarta International Circuit, Jakarta',
            'status' => 'published',
        ]);

        Event::create([
            'name' => 'Fun Ride Pushbike Festival',
            'description' => 'Event fun ride untuk semua kalangan dengan rute yang menyenangkan.',
            'start_date' => now()->addMonths(3)->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'registration_start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'registration_end' => now()->addMonths(2)->addDays(15)->format('Y-m-d H:i:s'),
            'location' => 'Taman Mini Indonesia Indah, Jakarta',
            'status' => 'published',
        ]);
    }
}
