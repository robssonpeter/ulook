<?php

use App\Models\Booking;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Service;
use App\Models\User;

function webPro(float $price = 20000): array
{
    $user = User::factory()->create(['role' => 'professional']);
    $professional = Professional::factory()->create(['user_id' => $user->id]);
    $service = ProfessionalService::create([
        'professional_id' => $professional->id,
        'service_id' => Service::firstOrCreate(['name' => 'Braiding'])->id,
        'name' => 'Box braids',
        'price' => $price,
        'duration_minutes' => 120,
        'is_active' => true,
    ]);

    return [$user, $professional, $service];
}

function bookingFor(User $customer, User $proUser, string $status = 'pending'): Booking
{
    return Booking::create([
        'customer_id' => $customer->id,
        'professional_id' => $proUser->id,
        'service_id' => Service::firstOrCreate(['name' => 'Braiding'])->id,
        'booking_date' => now()->addDay()->toDateString(),
        'booking_time' => '10:00',
        'total_price' => 20000,
        'status' => $status,
    ]);
}

// --- Sign up & sign in -----------------------------------------------------

test('signing up as a professional leads to business setup', function () {
    $this->post('/register', [
        'name' => 'Neema Massawe',
        'email' => 'neema@example.com',
        'phone' => '+255700000001',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'professional',
    ])->assertRedirect(route('business.setup'));

    // Role is only upgraded once the business profile exists.
    expect(User::where('email', 'neema@example.com')->first()->role)->toBe('customer');
});

test('signing up as a customer returns them to where they were', function () {
    $this->get(route('book.start', 42))->assertRedirect(route('login'));

    $this->post('/register', [
        'name' => 'Asha',
        'email' => 'asha@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'customer',
    ])->assertRedirect(route('book.start', 42));
});

test('users can sign in with their phone number', function () {
    $user = User::factory()->create(['phone' => '+255711111111', 'role' => 'customer']);

    $this->post('/login', ['email' => '+255711111111', 'password' => 'password'])
        ->assertRedirect(route('account.bookings'));

    $this->assertAuthenticatedAs($user);
});

test('professionals land on their business dashboard after signing in', function () {
    [$proUser] = webPro();

    $this->post('/login', ['email' => $proUser->email, 'password' => 'password'])
        ->assertRedirect(route('business.dashboard'));
});

// --- Customers -------------------------------------------------------------

test('customers book with the catalogue price, not the submitted one', function () {
    [$proUser, , $service] = webPro(45000);
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer)->post(route('bookings.store'), [
        'professional_service_id' => $service->id,
        'booking_date' => now()->addDay()->toDateString(),
        'booking_time' => '14:30',
        'type' => 'booking',
        'total_price' => 1,
    ])->assertRedirect(route('account.bookings'));

    $booking = Booking::sole();
    expect((float) $booking->total_price)->toBe(45000.0)
        ->and($booking->professional_id)->toBe($proUser->id)
        ->and($booking->status)->toBe('pending')
        ->and($booking->completion_code)->not->toBeNull();
});

test('home-service requests need an address', function () {
    [, , $service] = webPro();

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->post(route('bookings.store'), [
            'professional_service_id' => $service->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '09:00',
            'type' => 'request',
        ])->assertSessionHasErrors('customer_address');

    expect(Booking::count())->toBe(0);
});

test('professionals cannot place bookings', function () {
    [, , $service] = webPro();
    [$otherPro] = webPro();

    $this->actingAs($otherPro)->post(route('bookings.store'), [
        'professional_service_id' => $service->id,
        'booking_date' => now()->addDay()->toDateString(),
        'booking_time' => '09:00',
        'type' => 'booking',
    ])->assertSessionHasErrors('booking');
});

test('customers see their completion code and can cancel only their own pending bookings', function () {
    [$proUser] = webPro();
    $customer = User::factory()->create(['role' => 'customer']);
    $mine = bookingFor($customer, $proUser);
    $theirs = bookingFor(User::factory()->create(['role' => 'customer']), $proUser);

    $this->actingAs($customer)->get(route('account.bookings'))
        ->assertInertia(fn ($page) => $page
            ->component('Account/Bookings')
            ->has('bookings', 1)
            ->where('bookings.0.completion_code', $mine->completion_code));

    $this->patch(route('account.bookings.cancel', $theirs))->assertNotFound();
    $this->patch(route('account.bookings.cancel', $mine))->assertRedirect();
    expect($mine->fresh()->status)->toBe('cancelled');
});

// --- Professionals ---------------------------------------------------------

test('business pages send people without a profile to setup', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get(route('business.dashboard'))
        ->assertRedirect(route('business.setup'));
});

test('setup creates the profile, priced services, and upgrades the role', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $haircut = Service::create(['name' => 'Haircut']);

    $this->actingAs($user)->post(route('business.setup.store'), [
        'location' => 'Mbezi Beach',
        'bio' => 'Sharp fades.',
        'years_experience' => 4,
        'services' => [['service_id' => $haircut->id, 'price' => 10000, 'duration_minutes' => 40]],
    ])->assertRedirect(route('business.dashboard'));

    $professional = Professional::where('user_id', $user->id)->sole();
    expect($user->fresh()->role)->toBe('professional')
        ->and($professional->location)->toBe('Mbezi Beach')
        ->and((float) $professional->professionalServices()->sole()->price)->toBe(10000.0);
});

test('professionals accept and complete bookings with the customer code', function () {
    [$proUser] = webPro();
    $booking = bookingFor(User::factory()->create(['role' => 'customer']), $proUser);

    $this->actingAs($proUser)
        ->patch(route('business.bookings.update', $booking), ['status' => 'confirmed'])
        ->assertRedirect();
    expect($booking->fresh()->status)->toBe('confirmed');

    $this->patch(route('business.bookings.update', $booking), ['status' => 'completed', 'completion_code' => '0000x'])
        ->assertSessionHasErrors('completion_code');
    expect($booking->fresh()->status)->toBe('confirmed');

    $this->patch(route('business.bookings.update', $booking), ['status' => 'completed', 'completion_code' => $booking->completion_code])
        ->assertSessionHasNoErrors();
    expect($booking->fresh()->status)->toBe('completed');
});

test('professionals cannot touch other professionals bookings or services', function () {
    [$proUser] = webPro();
    [$otherPro, , $otherService] = webPro();
    $booking = bookingFor(User::factory()->create(['role' => 'customer']), $otherPro);

    $this->actingAs($proUser)
        ->patch(route('business.bookings.update', $booking), ['status' => 'confirmed'])
        ->assertNotFound();

    $this->patch(route('business.services.update', $otherService), [
        'name' => 'Hijacked', 'price' => 1, 'duration_minutes' => 10, 'is_active' => true,
    ])->assertNotFound();
});

test('professionals can save their working hours', function () {
    [$proUser, $professional] = webPro();

    $hours = collect(range(0, 6))->map(fn ($d) => [
        'day_of_week' => $d, 'is_closed' => $d === 0, 'open_time' => $d === 0 ? null : '08:00', 'close_time' => $d === 0 ? null : '17:00',
    ])->all();

    $this->actingAs($proUser)->put(route('business.hours.update'), ['hours' => $hours])->assertSessionHasNoErrors();

    expect($professional->workingHours()->count())->toBe(7)
        ->and($professional->workingHours()->where('day_of_week', 1)->value('open_time'))->toStartWith('08:00');
});
