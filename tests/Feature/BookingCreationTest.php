<?php

use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('booking creation succeeds when passing professional id instead of user id', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $professionalUser = User::factory()->create(['role' => 'professional']);
    $professional = Professional::factory()->create(['user_id' => $professionalUser->id]);
    $service = Service::factory()->create();

    Sanctum::actingAs($customer);

    $response = $this->postJson('/api/bookings', [
        'professional_id' => $professional->id, // This is the ID from 'professionals' table
        'service_id' => $service->id,
        'booking_date' => now()->addDay()->format('Y-m-d'),
        'booking_time' => '10:00',
        'total_price' => 50.00,
    ]);

    // This should now succeed because BookingController@store handles both IDs
    $response->assertStatus(201);
});

test('booking creation succeeds when passing professional user id', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $professionalUser = User::factory()->create(['role' => 'professional']);
    $professional = Professional::factory()->create(['user_id' => $professionalUser->id]);
    $service = Service::factory()->create();

    Sanctum::actingAs($customer);

    $response = $this->postJson('/api/bookings', [
        'professional_id' => $professionalUser->id,
        'service_id' => $service->id,
        'booking_date' => now()->addDay()->format('Y-m-d'),
        'booking_time' => '10:00',
        'total_price' => 50.00,
    ]);

    $response->assertStatus(201);
});
