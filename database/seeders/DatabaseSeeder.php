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
            return Service::firstOrCreate(['name' => $service]);
        });

        // Sample professional
        $professionalUser = User::create([
            'name' => 'John Professional',
            'phone' => '1234567890',
            'email' => 'professional@example.com',
            'password' => bcrypt('password'),
            'role' => 'professional',
        ]);

        $professional = Professional::create([
            'user_id' => $professionalUser->id,
            'bio' => 'Expert stylist with 10 years of experience.',
            'location' => 'Downtown New York',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'price_range' => '$$ - $$$',
            'is_verified' => true,
        ]);

        $professional->services()->attach(
            $serviceModels->pluck('id')->random(3)->mapWithKeys(function ($id) {
                return [$id => [
                    'name' => 'Premium Service',
                    'description' => 'Personalized service based on your needs.',
                    'price' => rand(20, 100),
                    'duration_minutes' => [30, 45, 60, 90][rand(0, 3)],
                    'is_active' => true,
                ]];
            })->toArray()
        );

        // Another professional (Nearby)
        $nearbyProfessionalUser = User::create([
            'name' => 'Nearby Pro',
            'phone' => '1112223333',
            'email' => 'nearby@example.com',
            'password' => bcrypt('password'),
            'role' => 'professional',
        ]);

        $nearbyProfessional = Professional::create([
            'user_id' => $nearbyProfessionalUser->id,
            'bio' => 'Fast and reliable beauty services.',
            'location' => 'Lower Manhattan',
            'latitude' => 40.7100,
            'longitude' => -74.0100,
            'price_range' => '$ - $$',
            'is_verified' => true,
        ]);

        $nearbyProfessional->services()->attach(
            $serviceModels->pluck('id')->random(2)->mapWithKeys(function ($id) {
                return [$id => [
                    'price' => rand(15, 60),
                    'duration_minutes' => [20, 30, 45][rand(0, 2)],
                    'is_active' => true,
                ]];
            })->toArray()
        );

        // Another professional (Far)
        $farProfessionalUser = User::create([
            'name' => 'Far Professional',
            'phone' => '5556667777',
            'email' => 'far@example.com',
            'password' => bcrypt('password'),
            'role' => 'professional',
        ]);

        $farProfessional = Professional::create([
            'user_id' => $farProfessionalUser->id,
            'bio' => 'Serving the Brooklyn area.',
            'location' => 'Brooklyn Heights',
            'latitude' => 40.6920,
            'longitude' => -73.9900,
            'price_range' => '$$$',
            'is_verified' => true,
        ]);

        $farProfessional->services()->attach(
            $serviceModels->pluck('id')->random(2)->mapWithKeys(function ($id) {
                return [$id => [
                    'price' => rand(80, 200),
                    'duration_minutes' => [60, 120][rand(0, 1)],
                    'is_active' => true,
                ]];
            })->toArray()
        );

        // Sample customer
        User::create([
            'name' => 'Jane Customer',
            'phone' => '0987654321',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
    }
}
