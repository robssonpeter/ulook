<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::with(['customer', 'professional', 'service', 'review']);

        // Check if the route name or path indicates professional dashboard
        if ($request->is('api/professional/*') || $user->role === 'professional') {
             $query->where('professional_id', $user->id);
        } else {
             $query->where('customer_id', $user->id);
        }

        return BookingResource::collection($query->paginate());
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'customer') {
            return response()->json(['message' => 'Only customers can create bookings.'], 403);
        }

        $validated = $request->validate([
            'professional_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (!$user || $user->role !== 'professional') {
                    $fail('The selected professional is invalid.');
                }
            }],
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
            'total_price' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
        ]);

        $booking = Booking::create([
            'customer_id' => $request->user()->id,
            'professional_id' => $validated['professional_id'],
            'service_id' => $validated['service_id'],
            'booking_date' => $validated['booking_date'],
            'booking_time' => $validated['booking_time'],
            'total_price' => $validated['total_price'],
            'deposit_amount' => $validated['deposit_amount'] ?? null,
            'status' => 'pending',
        ]);

        return new BookingResource($booking->load(['customer', 'professional', 'service']));
    }

    public function updateStatus(Request $request, $id)
    {
        if ($request->user()->role !== 'professional') {
            return response()->json(['message' => 'Only professionals can update booking status.'], 403);
        }

        $booking = Booking::where('professional_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $booking->status;

        // Validation logic for status transitions
        $allowed = false;

        if ($newStatus === 'cancelled') {
            $allowed = true; // Any status can be cancelled
        } elseif ($currentStatus === 'pending' && $newStatus === 'confirmed') {
            $allowed = true;
        } elseif ($currentStatus === 'confirmed' && $newStatus === 'completed') {
            $allowed = true;
        }

        if (!$allowed) {
            return response()->json(['message' => "Invalid status transition from $currentStatus to $newStatus."], 422);
        }

        $booking->update(['status' => $newStatus]);

        return new BookingResource($booking->load(['customer', 'professional', 'service']));
    }
}
