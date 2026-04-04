<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $booking = Booking::where('customer_id', $request->user()->id)
            ->where('status', 'completed')
            ->findOrFail($validated['booking_id']);

        if ($booking->review) {
            return response()->json(['message' => 'Review already exists for this booking.'], 422);
        }

        $review = Review::create($validated);

        return new ReviewResource($review);
    }
}
