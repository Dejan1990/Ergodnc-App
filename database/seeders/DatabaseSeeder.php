<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Dejan',
            'email' => 'dejan@mail.com',
            'password' => Hash::make('password')
        ]);

        $office1 = Office::factory()->create();
        $office2 = Office::factory()->create();
        $office3 = Office::factory()->create();

        $office1->update([
            'featured_image_id' => $office1->images()->create([
                'path' => '1.jpg'
            ])->id
        ]);

        $office2->update([
            'featured_image_id' => $office2->images()->create([
                'path' => '2.jpg'
            ])->id
        ]);

        $office3->update([
            'featured_image_id' => $office3->images()->create([
                'path' => '3.jpg'
            ])->id
        ]);

        Reservation::factory()->for($user)->for($office3)->create();
    }
}
