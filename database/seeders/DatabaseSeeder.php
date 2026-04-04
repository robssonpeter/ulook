<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sample services
        $services = [
            'Haircut',
            'Braiding',
            'Manicure',
            'Pedicure',
            'Facial',
            'Makeup',
            'Massage',
        ];

        $serviceModels = collect($services)->map(function ($service) {
            return Service::create(['name' => $service]);
        });

        // Sample professional
        $professionalUser = User::create([
            'name' => 'John Professional',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
            'role' => 'professional',
        ]);

        $professional = Professional::create([
            'user_id' => $professionalUser->id,
            'bio' => 'Expert stylist with 10 years of experience.',
            'location' => 'Downtown New York',
            'price_range' => '$$ - $$$',
            'is_verified' => true,
        ]);

        $professional->services()->attach($serviceModels->pluck('id')->random(3));

        // Sample customer
        User::create([
            'name' => 'Jane Customer',
            'phone' => '0987654321',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
    }
}
