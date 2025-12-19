<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::all();

        foreach ($events as $event) {
            if ($event->name === 'Pushbike Race Challenge 2025') {
                Category::create([
                    'event_id' => $event->id,
                    'name' => 'Race Competitive',
                    'description' => 'Kategori race untuk peserta yang ingin berkompetisi dengan serius.',
                    'status' => 'active',
                ]);

                Category::create([
                    'event_id' => $event->id,
                    'name' => 'Fun Ride',
                    'description' => 'Kategori fun ride untuk semua kalangan.',
                    'status' => 'active',
                ]);

                Category::create([
                    'event_id' => $event->id,
                    'name' => 'Kids Race',
                    'description' => 'Kategori khusus untuk anak-anak usia 5-12 tahun.',
                    'status' => 'active',
                ]);
            } else {
                Category::create([
                    'event_id' => $event->id,
                    'name' => 'Family Fun Ride',
                    'description' => 'Kategori fun ride untuk keluarga.',
                    'status' => 'active',
                ]);

                Category::create([
                    'event_id' => $event->id,
                    'name' => 'Individual Fun Ride',
                    'description' => 'Kategori fun ride untuk individu.',
                    'status' => 'active',
                ]);
            }
        }
    }
}
