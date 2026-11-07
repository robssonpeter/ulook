<?php

use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Service;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function sitePro(string $name, string $location, Service $service, float $price = 15000): Professional
{
    $user = User::factory()->create(['name' => $name, 'role' => 'professional']);
    $professional = Professional::factory()->create(['user_id' => $user->id, 'location' => $location]);
    ProfessionalService::create([
        'professional_id' => $professional->id,
        'service_id' => $service->id,
        'name' => $service->name,
        'price' => $price,
        'duration_minutes' => 45,
        'is_active' => true,
    ]);

    return $professional;
}

test('home page shows categories and featured professionals', function () {
    $braiding = Service::create(['name' => 'Braiding']);
    Service::create(['name' => 'Massage']);
    sitePro('Amina Braids', 'Mbezi Beach', $braiding);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('categories', 2)
            ->where('categories.0.name', 'Braiding')
            ->where('categories.0.count', 1)
            ->has('featured', 1)
            ->where('featured.0.name', 'Amina Braids')
            ->has('storeLinks.customer'));
});

test('professionals can be filtered by service and searched', function () {
    $braiding = Service::create(['name' => 'Braiding']);
    $haircut = Service::create(['name' => 'Haircut']);
    sitePro('Amina Braids', 'Mbezi Beach', $braiding);
    sitePro('Rube Barber', 'Kariakoo', $haircut);

    $this->get('/professionals?service='.$haircut->id)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Professionals')
            ->has('professionals', 1)
            ->where('professionals.0.name', 'Rube Barber')
            ->where('filters.service', $haircut->id));

    $this->get('/professionals?q=mbezi')
        ->assertInertia(fn (Assert $page) => $page
            ->has('professionals', 1)
            ->where('professionals.0.name', 'Amina Braids')
            ->where('pagination.total', 1));
});

test('profile page shows only active services', function () {
    $braiding = Service::create(['name' => 'Braiding']);
    $pro = sitePro('Amina Braids', 'Mbezi Beach', $braiding);
    ProfessionalService::create([
        'professional_id' => $pro->id,
        'service_id' => Service::create(['name' => 'Facial'])->id,
        'name' => 'Old facial',
        'price' => 5000,
        'duration_minutes' => 30,
        'is_active' => false,
    ]);

    $this->get('/professionals/'.$pro->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/ProfessionalShow')
            ->where('professional.name', 'Amina Braids')
            ->has('professional.services', 1)
            ->where('professional.services.0.price', 15000));
});

test('unknown professional returns 404', function () {
    $this->get('/professionals/999')->assertNotFound();
});
