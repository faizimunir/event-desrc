<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        foreach ($categories as $category) {
            if (str_contains($category->name, 'Race Competitive')) {
                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Early Bird',
                    'description' => 'Paket early bird dengan harga spesial.',
                    'price' => 150000.00,
                    'max_participants' => 100,
                    'status' => 'active',
                ]);

                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Regular',
                    'description' => 'Paket regular dengan harga normal.',
                    'price' => 200000.00,
                    'max_participants' => 200,
                    'status' => 'active',
                ]);
            } elseif (str_contains($category->name, 'Fun Ride')) {
                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Standard',
                    'description' => 'Paket standar untuk fun ride.',
                    'price' => 75000.00,
                    'max_participants' => 500,
                    'status' => 'active',
                ]);

                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Premium',
                    'description' => 'Paket premium dengan kaos dan merchandise.',
                    'price' => 125000.00,
                    'max_participants' => 200,
                    'status' => 'active',
                ]);
            } elseif (str_contains($category->name, 'Kids')) {
                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Kids Package',
                    'description' => 'Paket khusus untuk anak-anak dengan harga terjangkau.',
                    'price' => 50000.00,
                    'max_participants' => 150,
                    'status' => 'active',
                ]);
            } elseif (str_contains($category->name, 'Family')) {
                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Family Package (2-3 orang)',
                    'description' => 'Paket keluarga untuk 2-3 orang dengan harga spesial.',
                    'price' => 200000.00,
                    'max_participants' => 100,
                    'status' => 'active',
                ]);
            } else {
                Package::create([
                    'event_id' => $category->event_id,
                    'category_id' => $category->id,
                    'name' => 'Individual Package',
                    'description' => 'Paket untuk individu.',
                    'price' => 100000.00,
                    'max_participants' => 300,
                    'status' => 'active',
                ]);
            }
        }
    }
}
