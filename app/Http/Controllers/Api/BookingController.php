<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
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

    public function store(Request $request, BookingService $bookings)
    {
        if ($request->user()->role !== 'customer') {
            return response()->json(['message' => 'Only customers can create bookings.'], 403);
        }

        $data = $request->validate([
            'professional_id'         => 'required',
            'service_id'              => 'nullable|exists:services,id',
            'professional_service_id' => 'nullable|exists:professional_services,id',
            'booking_date'            => 'required|date|after_or_equal:today',
            'booking_time'            => 'required',
            'total_price'             => 'required|numeric|min:0',
            'deposit_amount'          => 'nullable|numeric|min:0',
            'type'                    => 'nullable|in:booking,request',
            'venue_type'              => 'nullable|in:home,office,hotel,event',
            'customer_address'        => 'nullable|string|max:500',
            'customer_latitude'       => 'nullable|numeric|between:-90,90',
            'customer_longitude'      => 'nullable|numeric|between:-180,180',
        ]);

        $booking = $bookings->create($request->user(), $data);

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function updateStatus(Request $request, BookingService $bookings, $id)
    {
        $user = $request->user();

        if ($user->role === 'customer') {
            $booking = Booking::where('customer_id', $user->id)->findOrFail($id);

            if ($booking->status !== 'pending') {
                return response()->json(['message' => 'You can only cancel pending bookings.'], 422);
            }

            $request->validate(['status' => ['required', Rule::in(['cancelled'])]]);

            $bookings->cancelByCustomer($user, $booking);

            return new BookingResource($booking->load(['customer', 'professional', 'service']));
        }

        if ($user->role !== 'professional') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $booking = Booking::where('professional_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'status'          => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'completion_code' => ['nullable', 'string'],
        ]);

        $bookings->transitionByProfessional($booking, $validated['status'], $validated['completion_code'] ?? null);

        return new BookingResource($booking->load(['customer', 'professional', 'service']));
    }
}
