<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** A customer's bookings on the website. */
class AccountController extends Controller
{
    public function bookings(Request $request): Response
    {
        $bookings = Booking::with(['professional', 'service', 'professionalService'])
            ->where('customer_id', $request->user()->id)
            ->latest('booking_date')
            ->latest('booking_time')
            ->get();

        // Bookings store the professional's user id; profiles are keyed by professionals.id.
        $profileIds = Professional::whereIn('user_id', $bookings->pluck('professional_id'))->pluck('id', 'user_id');

        return Inertia::render('Account/Bookings', [
            'bookings' => $bookings->map(fn (Booking $b) => [
                'id' => $b->id,
                'service' => $b->professionalService?->name ?? $b->service?->name ?? 'Service',
                'professional' => $b->professional?->name,
                'professional_profile_id' => $profileIds[$b->professional_id] ?? null,
                'date' => substr((string) $b->booking_date, 0, 10),
                'time' => substr((string) $b->booking_time, 0, 5),
                'price' => (float) $b->total_price,
                'status' => $b->status,
                'type' => $b->type ?? 'booking',
                'address' => $b->customer_address,
                // Shown to the customer so they can read it to the professional on completion.
                'completion_code' => in_array($b->status, ['pending', 'confirmed'], true) ? $b->completion_code : null,
            ]),
        ]);
    }

    /** Guests who click "Book" land here after signing in. */
    public function startBooking(Professional $professional): RedirectResponse
    {
        return redirect()->to(route('professionals.show', $professional).'#book');
    }

    public function storeBooking(Request $request, BookingService $bookings): RedirectResponse
    {
        if ($request->user()->role !== 'customer') {
            return back()->withErrors(['booking' => 'Bookings are made from a customer account.']);
        }

        $validated = $request->validate([
            'professional_service_id' => ['required', 'integer', Rule::exists('professional_services', 'id')->where('is_active', true)],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::in(['booking', 'request'])],
            'customer_address' => ['nullable', 'required_if:type,request', 'string', 'max:500'],
        ], [
            'customer_address.required_if' => 'Tell the professional where to come.',
        ]);

        // Price comes from the professional's catalogue, never the browser.
        $service = ProfessionalService::findOrFail($validated['professional_service_id']);

        $bookings->create($request->user(), [
            ...$validated,
            'professional_id' => $service->professional_id,
            'total_price' => $service->price,
            'venue_type' => $validated['type'] === 'request' ? 'home' : null,
        ]);

        return redirect()->route('account.bookings')
            ->with('flash', ['banner' => 'Booking sent! The professional will confirm shortly.', 'bannerStyle' => 'success']);
    }

    public function cancel(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        abort_unless($booking->customer_id === $request->user()->id, 404);

        $bookings->cancelByCustomer($request->user(), $booking);

        return back()->with('flash', ['banner' => 'Booking cancelled.', 'bannerStyle' => 'success']);
    }
}
