<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\ProfessionalWorkingHours;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** YuLuk Business on the web: onboarding, bookings, and the professional's catalogue. */
class BusinessController extends Controller
{
    public function setup(Request $request): Response|RedirectResponse
    {
        if (Professional::where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('business.dashboard');
        }

        return Inertia::render('Business/Setup', [
            'services' => Service::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeSetup(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (Professional::where('user_id', $user->id)->exists()) {
            return redirect()->route('business.dashboard');
        }

        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'distinct', 'exists:services,id'],
            'services.*.price' => ['required', 'numeric', 'min:0'],
            'services.*.duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
        ], [
            'services.required' => 'Pick at least one service you offer.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $professional = Professional::create([
                'user_id' => $user->id,
                'location' => $validated['location'],
                'bio' => $validated['bio'] ?? null,
                'years_experience' => $validated['years_experience'] ?? null,
            ]);

            $names = Service::whereIn('id', collect($validated['services'])->pluck('service_id'))->pluck('name', 'id');
            foreach ($validated['services'] as $s) {
                $professional->professionalServices()->create([
                    'service_id' => $s['service_id'],
                    'name' => $names[$s['service_id']],
                    'price' => $s['price'],
                    'duration_minutes' => $s['duration_minutes'],
                    'is_active' => true,
                ]);
            }

            // Same upgrade the business app does when it creates a profile.
            $user->update(['role' => 'professional']);
        });

        return redirect()->route('business.dashboard')
            ->with('flash', ['banner' => 'Welcome to YuLuk Business! Your profile is live.', 'bannerStyle' => 'success']);
    }

    public function dashboard(Request $request): Response
    {
        $professional = $request->attributes->get('professional');

        $bookings = Booking::with(['customer', 'service', 'professionalService'])
            ->where('professional_id', $request->user()->id)
            ->orderBy('booking_date')
            ->orderBy('booking_time')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'service' => $b->professionalService?->name ?? $b->service?->name ?? 'Service',
                'customer' => $b->customer?->name,
                'customer_phone' => $b->customer?->phone,
                'date' => substr((string) $b->booking_date, 0, 10),
                'time' => substr((string) $b->booking_time, 0, 5),
                'price' => (float) $b->total_price,
                'status' => $b->status,
                'type' => $b->type ?? 'booking',
                'address' => $b->customer_address,
                'needs_code' => (bool) $b->completion_code,
            ]);

        return Inertia::render('Business/Dashboard', [
            'profileId' => $professional->id,
            'bookings' => $bookings,
        ]);
    }

    public function updateBooking(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        abort_unless($booking->professional_id === $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'completed', 'cancelled'])],
            'completion_code' => ['nullable', 'string', 'max:10'],
        ]);

        $bookings->transitionByProfessional($booking, $validated['status'], $validated['completion_code'] ?? null);

        $message = match ($validated['status']) {
            'confirmed' => 'Booking accepted.',
            'completed' => 'Marked as completed. Nice work!',
            'cancelled' => 'Booking declined.',
        };

        return back()->with('flash', ['banner' => $message, 'bannerStyle' => 'success']);
    }

    public function profile(Request $request): Response
    {
        $professional = $request->attributes->get('professional')->load(['professionalServices.service', 'workingHours']);

        return Inertia::render('Business/Profile', [
            'professional' => [
                'id' => $professional->id,
                'location' => $professional->location,
                'bio' => $professional->bio,
                'years_experience' => $professional->years_experience,
            ],
            'catalogue' => $professional->professionalServices->map(fn (ProfessionalService $s) => [
                'id' => $s->id,
                'service_id' => $s->service_id,
                'category' => $s->service?->name,
                'name' => $s->name ?? $s->service?->name,
                'price' => (float) $s->price,
                'duration_minutes' => $s->duration_minutes,
                'is_active' => (bool) $s->is_active,
            ]),
            'hours' => collect(range(0, 6))->map(function (int $day) use ($professional) {
                $h = $professional->workingHours->firstWhere('day_of_week', $day);

                return [
                    'day_of_week' => $day,
                    'open_time' => $h?->open_time ? substr($h->open_time, 0, 5) : '09:00',
                    'close_time' => $h?->close_time ? substr($h->close_time, 0, 5) : '18:00',
                    'is_closed' => $h ? (bool) $h->is_closed : $day === 0,
                ];
            }),
            'services' => Service::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        $request->attributes->get('professional')->update($validated);

        return back()->with('flash', ['banner' => 'Profile saved.', 'bannerStyle' => 'success']);
    }

    public function addService(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
        ]);

        $request->attributes->get('professional')->professionalServices()->create([...$validated, 'is_active' => true]);

        return back()->with('flash', ['banner' => 'Service added.', 'bannerStyle' => 'success']);
    }

    public function updateService(Request $request, ProfessionalService $service): RedirectResponse
    {
        abort_unless($service->professional_id === $request->attributes->get('professional')->id, 404);

        $service->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'is_active' => ['required', 'boolean'],
        ]));

        return back()->with('flash', ['banner' => 'Service updated.', 'bannerStyle' => 'success']);
    }

    public function saveHours(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.is_closed' => ['required', 'boolean'],
            'hours.*.open_time' => ['nullable', 'required_if:hours.*.is_closed,false', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'required_if:hours.*.is_closed,false', 'date_format:H:i'],
        ]);

        $professional = $request->attributes->get('professional');

        foreach ($validated['hours'] as $h) {
            ProfessionalWorkingHours::updateOrCreate(
                ['professional_id' => $professional->id, 'day_of_week' => $h['day_of_week']],
                [
                    'open_time' => $h['is_closed'] ? null : $h['open_time'],
                    'close_time' => $h['is_closed'] ? null : $h['close_time'],
                    'is_closed' => $h['is_closed'],
                ],
            );
        }

        return back()->with('flash', ['banner' => 'Working hours saved.', 'bannerStyle' => 'success']);
    }
}
