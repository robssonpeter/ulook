<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\NewBookingNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::with(['customer', 'professional', 'service', 'professionalService', 'review']);

        if ($request->is('api/professional/*') || $user->role === 'professional') {
            $query->where('professional_id', $user->id);
        } else {
            $query->where('customer_id', $user->id);
        }

        return BookingResource::collection($query->latest()->paginate());
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'customer') {
            return response()->json(['message' => 'Only customers can create bookings.'], 403);
        }

        $request->validate([
            'professional_id'       => 'required',
            'service_id'            => 'nullable|exists:services,id',
            'professional_service_id' => 'nullable|exists:professional_services,id',
            'booking_date'          => 'required|date|after_or_equal:today',
            'booking_time'          => 'required',
            'total_price'           => 'required|numeric|min:0',
            'deposit_amount'        => 'nullable|numeric|min:0',
        ]);

        if (! $request->service_id && ! $request->professional_service_id) {
            return response()->json(['message' => 'Service is required.'], 422);
        }

        $professionalId = $request->professional_id;

        $professionalUser = User::where('id', $professionalId)->where('role', 'professional')->first();

        if (! $professionalUser) {
            $professional = Professional::find($professionalId);
            if ($professional) {
                $professionalUser = User::where('id', $professional->user_id)->where('role', 'professional')->first();
            }
        }

        if (! $professionalUser) {
            return response()->json([
                'message' => 'The selected professional is invalid.',
                'errors'  => ['professional_id' => ['The selected professional is invalid.']],
            ], 422);
        }

        $booking = Booking::create([
            'customer_id'           => $request->user()->id,
            'professional_id'       => $professionalUser->id,
            'service_id'            => $request->service_id ?? ($request->professional_service_id ? ProfessionalService::find($request->professional_service_id)?->service_id : null),
            'professional_service_id' => $request->professional_service_id,
            'booking_date'          => $request->booking_date,
            'booking_time'          => $request->booking_time,
            'total_price'           => $request->total_price,
            'deposit_amount'        => $request->deposit_amount ?? null,
            'status'                => 'pending',
        ]);

        $booking->load(['customer', 'professional', 'service', 'professionalService']);

        // Notify professional of new booking
        if ($professionalUser->email) {
            try {
                $professionalUser->notify(new NewBookingNotification($booking));
            } catch (\Exception $e) {
                // Non-fatal: log but don't fail the request
            }
        }

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'customer') {
            $booking = Booking::where('customer_id', $user->id)->findOrFail($id);

            if ($booking->status !== 'pending') {
                return response()->json(['message' => 'You can only cancel pending bookings.'], 422);
            }

            $request->validate(['status' => ['required', Rule::in(['cancelled'])]]);

            $currentStatus = $booking->status;
            $booking->update(['status' => 'cancelled']);

            // Notify customer of their own cancellation confirmation
            if ($user->email) {
                try {
                    $user->notify(new BookingStatusNotification($booking->load(['professionalService', 'service']), $currentStatus));
                } catch (\Exception $e) {
                    // Non-fatal
                }
            }

            return new BookingResource($booking->load(['customer', 'professional', 'service']));
        }

        if ($user->role !== 'professional') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $booking = Booking::where('professional_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
        ]);

        $newStatus     = $validated['status'];
        $currentStatus = $booking->status;

        $allowed = false;
        if ($newStatus === 'cancelled') {
            $allowed = true;
        } elseif ($currentStatus === 'pending' && $newStatus === 'confirmed') {
            $allowed = true;
        } elseif ($currentStatus === 'confirmed' && $newStatus === 'completed') {
            $allowed = true;
        }

        if (! $allowed) {
            return response()->json(['message' => "Invalid status transition from {$currentStatus} to {$newStatus}."], 422);
        }

        $booking->update(['status' => $newStatus]);

        // Notify the customer of the status change
        $customer = User::find($booking->customer_id);
        if ($customer?->email) {
            try {
                $customer->notify(new BookingStatusNotification($booking->load(['professionalService', 'service']), $currentStatus));
            } catch (\Exception $e) {
                // Non-fatal
            }
        }

        return new BookingResource($booking->load(['customer', 'professional', 'service']));
    }
}
