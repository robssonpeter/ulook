<?php

use App\Models\Booking;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function passportPro(): array
{
    $user = User::factory()->create(['role' => 'professional']);
    $professional = Professional::factory()->create(['user_id' => $user->id]);

    return [$user, $professional];
}

function completedJob(User $proUser, string $serviceName, bool $verified = true, ?int $rating = null): Booking
{
    $customer = User::factory()->create(['role' => 'customer']);
    $service = Service::firstOrCreate(['name' => $serviceName]);
    $booking = Booking::create([
        'customer_id'     => $customer->id,
        'professional_id' => $proUser->id,
        'service_id'      => $service->id,
        'booking_date'    => now()->subDay()->toDateString(),
        'booking_time'    => '10:00',
        'status'          => 'completed',
        'completed_at'    => $verified ? now() : null,
        'total_price'     => 20000,
    ]);
    if ($rating) {
        Review::create(['booking_id' => $booking->id, 'rating' => $rating, 'comment' => 'Great']);
    }

    return $booking;
}

test('professionals get a passport id on creation', function () {
    [, $professional] = passportPro();

    expect($professional->passport_id)->not->toBeEmpty();
});

test('public passport counts only code-verified jobs and attributes them to skills', function () {
    [$user, $professional] = passportPro();
    $professional->skills()->create(['name' => 'Braiding']);
    $professional->skills()->create(['name' => 'Makeup']);

    completedJob($user, 'Knotless braids', true, 5);
    completedJob($user, 'Box Braids', true, 4);
    completedJob($user, 'Cornrows', false); // completed before codes existed: not verified

    $response = $this->getJson("/api/professionals/{$professional->id}/passport")->assertOk();

    $response->assertJsonPath('data.stats.services_completed', 2)
        ->assertJsonPath('data.stats.reviews_count', 2)
        ->assertJsonPath('data.stats.average_rating', 4.5)
        ->assertJsonCount(2, 'data.recent_reviews')
        ->assertJsonStructure(['data' => ['recent_reviews' => [['id', 'rating', 'comment', 'reviewer_name']]]]);

    $skills = collect($response->json('data.skills'))->keyBy('name');
    expect($skills['Braiding']['jobs_completed'])->toBe(2)
        ->and($skills['Braiding']['is_verified'])->toBeTrue()
        ->and($skills['Makeup']['is_verified'])->toBeFalse();
});

test('public passport hides pending certificates and private past workplaces', function () {
    [, $professional] = passportPro();
    $professional->certifications()->create(['title' => 'Advanced Hair', 'status' => 'verified', 'file_url' => '/storage/x.pdf']);
    $professional->certifications()->create(['title' => 'Pending Course', 'status' => 'pending']);
    $professional->workplaces()->create(['name' => 'Salon A', 'type' => 'station', 'started_on' => '2024-01-01', 'ended_on' => '2025-01-01', 'is_public' => false]);
    $professional->workplaces()->create(['name' => 'Salon B', 'type' => 'station', 'area' => 'Masaki', 'started_on' => '2025-02-01']);

    $data = $this->getJson("/api/professionals/{$professional->id}/passport")->assertOk()->json('data');

    expect(collect($data['certifications'])->pluck('title')->all())->toBe(['Advanced Hair'])
        ->and($data['certifications'][0]['file_url'])->toBeNull()
        ->and(collect($data['workplaces'])->pluck('name')->all())->toBe(['Salon B'])
        ->and(collect($data['timeline'])->pluck('title'))->toContain('Earned Advanced Hair', 'Started at Salon B, Masaki')
        ->and(collect($data['timeline'])->pluck('title'))->not->toContain('Started at Salon A');
});

test('owner sees everything and can manage skills, certificates and workplaces', function () {
    Storage::fake('public');
    [$user, $professional] = passportPro();
    Sanctum::actingAs($user);

    $this->postJson('/api/professional/skills', ['name' => 'Nails'])->assertCreated();
    $this->postJson('/api/professional/skills', ['name' => 'Nails'])->assertUnprocessable();

    $this->post('/api/professional/certifications', [
        'title' => 'Nail Academy',
        'issuer' => 'Dar Beauty School',
        'issued_on' => '2025-06-01',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.status', 'pending');

    $placeId = $this->postJson('/api/professional/workplaces', [
        'name' => 'Salon A', 'type' => 'station', 'started_on' => '2024-01-01',
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/professional/workplaces/{$placeId}", ['ended_on' => '2025-01-01', 'is_public' => false])
        ->assertOk()->assertJsonPath('data.is_current', false);

    $data = $this->getJson('/api/professional/passport')->assertOk()->json('data');
    expect($data['certifications'][0]['status'])->toBe('pending')
        ->and($data['certifications'][0]['file_url'])->not->toBeNull()
        ->and($data['workplaces'])->toHaveCount(1)
        ->and($data['skills'][0]['name'])->toBe('Nails');
});

test('a professional cannot touch another professional\'s passport items', function () {
    [, $other] = passportPro();
    $skill = $other->skills()->create(['name' => 'Spa']);
    [$user] = passportPro();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/professional/skills/{$skill->id}")->assertNotFound();
    expect($skill->fresh())->not->toBeNull();
});

test('career timeline marks verified service milestones', function () {
    [$user, $professional] = passportPro();
    foreach (range(1, 10) as $i) {
        completedJob($user, 'Haircut');
    }

    $titles = collect($this->getJson("/api/professionals/{$professional->id}/passport")->json('data.timeline'))->pluck('title');

    expect($titles)->toContain('10 verified services completed', 'Joined YULUK');
});
