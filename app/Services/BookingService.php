<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\NewBookingNotification;
use Illuminate\Validation\ValidationException;

/**
 * Booking rules shared by the mobile API and the website, so both
 * enforce the same transitions and completion-code check.
 */
class BookingService
{
    /**
     * @param  array  $data  validated booking fields (see BookingController::store)
     */
    public function create(User $customer, array $data): Booking
    {
        if (($data['type'] ?? 'booking') === 'request' && empty($data['customer_address'])) {
            throw ValidationException::withMessages([
                'customer_address' => 'Address is required for home service requests.',
            ]);
        }

        if (empty($data['service_id']) && empty($data['professional_service_id'])) {
            throw ValidationException::withMessages(['service_id' => 'Service is required.']);
        }

        $professionalUser = $this->resolveProfessionalUser($data['professional_id']);

        if (! $professionalUser) {
            throw ValidationException::withMessages([
                'professional_id' => 'The selected professional is invalid.',
            ]);
        }

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'professional_id' => $professionalUser->id,
            'service_id' => $data['service_id']
                ?? (! empty($data['professional_service_id']) ? ProfessionalService::find($data['professional_service_id'])?->service_id : null),
            'professional_service_id' => $data['professional_service_id'] ?? null,
            'booking_date' => $data['booking_date'],
            'booking_time' => $data['booking_time'],
            'total_price' => $data['total_price'],
            'deposit_amount' => $data['deposit_amount'] ?? null,
            'status' => 'pending',
            'type' => $data['type'] ?? 'booking',
            'venue_type' => $data['venue_type'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'customer_latitude' => $data['customer_latitude'] ?? null,
            'customer_longitude' => $data['customer_longitude'] ?? null,
        ]);

        $booking->load(['customer', 'professional', 'service', 'professionalService']);

        if ($professionalUser->email) {
            rescue(fn () => $professionalUser->notify(new NewBookingNotification($booking)), report: false);
        }

        return $booking;
    }

    /** A customer may only cancel their own pending booking. */
    public function cancelByCustomer(User $customer, Booking $booking): Booking
    {
        if ($booking->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'You can only cancel pending bookings.']);
        }

        $previous = $booking->status;
        $booking->update(['status' => 'cancelled']);

        if ($customer->email) {
            rescue(fn () => $customer->notify(new BookingStatusNotification($booking->load(['professionalService', 'service']), $previous)), report: false);
        }

        return $booking;
    }

    /**
     * Professional-side transitions: pending→confirmed, confirmed→completed
     * (with the customer's completion code), or cancel at any point.
     */
    public function transitionByProfessional(Booking $booking, string $status, ?string $completionCode = null): Booking
    {
        $previous = $booking->status;

        $allowed = $status === 'cancelled'
            || ($previous === 'pending' && $status === 'confirmed')
            || ($previous === 'confirmed' && $status === 'completed');

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition from {$previous} to {$status}.",
            ]);
        }

        if ($status === 'completed') {
            // Proof of delivery: the code the customer sees in their app.
            if ($booking->completion_code
                && ! hash_equals($booking->completion_code, trim((string) $completionCode))) {
                throw ValidationException::withMessages([
                    'completion_code' => 'Incorrect completion code. Ask the customer for the 4-digit code shown in their app.',
                ]);
            }
            $booking->update(['status' => 'completed', 'completed_at' => now()]);
        } else {
            $booking->update(['status' => $status]);
        }

        $customer = User::find($booking->customer_id);
        if ($customer?->email) {
            rescue(fn () => $customer->notify(new BookingStatusNotification($booking->load(['professionalService', 'service']), $previous)), report: false);
        }

        return $booking;
    }

    /** Accepts either a professional's user id or a professionals.id. */
    private function resolveProfessionalUser(int|string $id): ?User
    {
        $user = User::where('id', $id)->where('role', 'professional')->first();

        if (! $user && $professional = Professional::find($id)) {
            $user = User::where('id', $professional->user_id)->where('role', 'professional')->first();
        }

        return $user;
    }
}
