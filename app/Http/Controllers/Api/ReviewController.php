<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function indexForProfessional($id)
    {
        $professional = Professional::findOrFail($id);

        $reviews = Review::whereHas('booking', function ($q) use ($professional) {
            $q->where('professional_id', $professional->user_id)
              ->where('status', 'completed');
        })->with(['booking.customer'])->latest()->get();

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string',
        ]);

        $booking = Booking::where('customer_id', $request->user()->id)
            ->where('status', 'completed')
            ->findOrFail($validated['booking_id']);

        if ($booking->review) {
            return response()->json(['message' => 'Review already exists for this booking.'], 422);
        }

        $review = Review::create($validated);

        return new ReviewResource($review->load(['booking.customer']));
    }
}
