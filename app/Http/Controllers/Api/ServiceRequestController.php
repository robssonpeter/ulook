<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Http\Resources\ServiceRequestResponseResource;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    // ── Customer: post an open request ────────────────────────────────────────

    public function store(Request $request)
    {
        if ($request->user()->role !== 'customer') {
            return response()->json(['message' => 'Only customers can post service requests.'], 403);
        }

        $validated = $request->validate([
            'service_id'         => 'nullable|exists:services,id',
            'description'        => 'nullable|string|max:1000',
            'customer_address'   => 'required|string|max:500',
            'customer_latitude'  => 'required|numeric|between:-90,90',
            'customer_longitude' => 'required|numeric|between:-180,180',
            'requested_date'     => 'required|date|after_or_equal:today',
            'requested_time'     => 'required',
            'radius_km'          => 'nullable|numeric|min:1|max:100',
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $request->user()->id,
            ...$validated,
            'radius_km'   => $validated['radius_km'] ?? 25,
            'status'      => 'open',
        ]);

        return (new ServiceRequestResource($serviceRequest->load('service')))
            ->response()->setStatusCode(201);
    }

    // ── Customer: view their own requests (with responses) ────────────────────

    public function myRequests(Request $request)
    {
        $requests = ServiceRequest::where('customer_id', $request->user()->id)
            ->with(['service', 'responses.professional.user'])
            ->withCount('responses')
            ->latest()
            ->get();

        return ServiceRequestResource::collection($requests);
    }

    // ── Customer: cancel their request ────────────────────────────────────────

    public function cancel(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::where('customer_id', $request->user()->id)
            ->findOrFail($id);

        if ($serviceRequest->status !== 'open') {
            return response()->json(['message' => 'Only open requests can be cancelled.'], 422);
        }

        $serviceRequest->update(['status' => 'cancelled']);

        return new ServiceRequestResource($serviceRequest);
    }

    // ── Customer: accept a professional's response → create booking ───────────

    public function acceptResponse(Request $request, $requestId, $responseId)
    {
        $serviceRequest = ServiceRequest::where('customer_id', $request->user()->id)
            ->where('status', 'open')
            ->with('responses')
            ->findOrFail($requestId);

        $response = $serviceRequest->responses()->findOrFail($responseId);

        // Create the booking
        $booking = Booking::create([
            'customer_id'      => $request->user()->id,
            'professional_id'  => $response->professional->user_id,
            'service_id'       => $serviceRequest->service_id,
            'booking_date'     => $serviceRequest->requested_date->format('Y-m-d'),
            'booking_time'     => $serviceRequest->requested_time,
            'total_price'      => $response->price_offered,
            'status'           => 'confirmed',
            'type'             => 'request',
            'customer_address' => $serviceRequest->customer_address,
            'customer_latitude'  => $serviceRequest->customer_latitude,
            'customer_longitude' => $serviceRequest->customer_longitude,
        ]);

        // Mark this response accepted, others rejected
        $serviceRequest->responses()
            ->where('id', '!=', $responseId)
            ->update(['status' => 'rejected']);

        $response->update(['status' => 'accepted']);

        // Mark the service request as matched
        $serviceRequest->update([
            'status'                  => 'matched',
            'matched_professional_id' => $response->professional->user_id,
            'matched_booking_id'      => $booking->id,
        ]);

        // Notify the accepted professional
        AppNotification::create([
            'user_id' => $response->professional->user_id,
            'type'    => 'response_accepted',
            'title'   => 'You\'ve been hired!',
            'body'    => $request->user()->name . ' accepted your bid. Booking #' . $booking->id . ' confirmed.',
            'data'    => ['booking_id' => $booking->id],
        ]);

        return response()->json([
            'message'    => 'Professional accepted. Booking confirmed.',
            'booking_id' => $booking->id,
        ]);
    }

    // ── Professional: see nearby open requests ────────────────────────────────

    public function nearby(Request $request)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();

        if (! $professional->latitude || ! $professional->longitude) {
            return response()->json([
                'message' => 'Update your profile with GPS coordinates to see nearby requests.',
                'data'    => [],
            ]);
        }

        $lat = (float) $professional->latitude;
        $lng = (float) $professional->longitude;

        // Find open requests where the customer is within the request's own radius
        // AND within a configurable max distance we pass as a query param (default 50 km)
        $maxKm = (float) $request->input('max_km', 50);

        $requests = ServiceRequest::where('status', 'open')
            ->with(['service', 'customer', 'responses' => function ($q) use ($professional) {
                $q->where('professional_id', $professional->id);
            }])
            ->withCount('responses')
            ->selectRaw(
                'service_requests.*, (6371 * acos(
                    cos(radians(?)) * cos(radians(customer_latitude))
                    * cos(radians(customer_longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(customer_latitude))
                )) AS distance',
                [$lat, $lng, $lat]
            )
            ->havingRaw('distance <= ?', [$maxKm])
            ->orderBy('distance')
            ->get();

        return ServiceRequestResource::collection($requests);
    }

    // ── Professional: respond to a request ───────────────────────────────────

    public function respond(Request $request, $id)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();

        $serviceRequest = ServiceRequest::where('status', 'open')->findOrFail($id);

        if ($serviceRequest->customer_id === $user->id) {
            return response()->json(['message' => 'You cannot respond to your own request.'], 403);
        }

        $validated = $request->validate([
            'price_offered' => 'required|numeric|min:0',
            'message'       => 'nullable|string|max:500',
        ]);

        $response = ServiceRequestResponse::updateOrCreate(
            [
                'service_request_id' => $serviceRequest->id,
                'professional_id'    => $professional->id,
            ],
            [
                'price_offered' => $validated['price_offered'],
                'message'       => $validated['message'] ?? null,
                'status'        => 'pending',
            ]
        );

        // Notify the customer that a professional responded
        AppNotification::create([
            'user_id' => $serviceRequest->customer_id,
            'type'    => 'new_response',
            'title'   => 'New bid received!',
            'body'    => $user->name . ' offered $' . number_format($validated['price_offered'], 2) . ' for your request.',
            'data'    => [
                'service_request_id' => $serviceRequest->id,
                'response_id'        => $response->id,
            ],
        ]);

        return (new ServiceRequestResponseResource(
            $response->load('professional.user')
        ))->response()->setStatusCode(201);
    }
}
