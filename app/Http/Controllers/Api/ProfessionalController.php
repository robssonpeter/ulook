<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionalResource;
use App\Models\Professional;
use Illuminate\Http\Request;

class ProfessionalController extends Controller
{
    public function index(Request $request)
    {
        $query = Professional::with(['user', 'services']);

        if ($request->has(['lat', 'lng'])) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;

            // Simple distance calculation using Pythagoras (for small distances)
            // For more accuracy, use Haversine formula
            $query->selectRaw(
                '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$lat, $lng, $lat]
            )->orderBy('distance');
        }

        $professionals = $query->paginate();

        return ProfessionalResource::collection($professionals);
    }

    public function show($id)
    {
        $professional = Professional::with(['user', 'services'])->findOrFail($id);

        return new ProfessionalResource($professional);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user already has a professional profile
        if (Professional::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Professional profile already exists.'], 400);
        }

        $validated = $request->validate([
            'bio' => 'nullable|string',
            'location' => 'required|string',
            'price_range' => 'nullable|string',
            'services' => 'required|array',
            'services.*' => 'exists:services,id',
        ]);

        $professional = Professional::create([
            'user_id' => $user->id,
            'bio' => $validated['bio'],
            'location' => $validated['location'],
            'price_range' => $validated['price_range'],
        ]);

        $professional->services()->attach($validated['services']);

        // Update user role to professional
        $user->update(['role' => 'professional']);

        return new ProfessionalResource($professional->load(['user', 'services']));
    }

    public function getServices($id)
    {
        $professional = Professional::findOrFail($id);

        return response()->json([
            'data' => $professional->professionalServices()->with('service')->get(),
        ]);
    }

    public function addService(Request $request)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $professionalService = $professional->professionalServices()->create($validated);

        return response()->json([
            'message' => 'Service added to catalog successfully.',
            'data' => $professionalService->load('service'),
        ], 201);
    }

    public function updateService(Request $request, $id)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();
        $professionalService = $professional->professionalServices()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $professionalService->update($validated);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data' => $professionalService->load('service'),
        ]);
    }

    public function toggleService(Request $request, $id)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();
        $professionalService = $professional->professionalServices()->findOrFail($id);

        $professionalService->update([
            'is_active' => ! $professionalService->is_active,
        ]);

        return response()->json([
            'message' => 'Service status toggled successfully.',
            'data' => $professionalService,
        ]);
    }
}
