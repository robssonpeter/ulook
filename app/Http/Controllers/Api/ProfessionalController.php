<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\ProfessionalResource;
use App\Models\Professional;

class ProfessionalController extends Controller
{
    public function index()
    {
        $professionals = Professional::with(['user', 'services'])->paginate();
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

    public function update(Request $request, $id)
    {
        $professional = Professional::findOrFail($id);

        if ($request->user()->id !== $professional->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'bio' => 'nullable|string',
            'location' => 'nullable|string',
            'price_range' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
        ]);

        $professional->update($request->only(['bio', 'location', 'price_range']));

        if ($request->has('services')) {
            $professional->services()->sync($validated['services']);
        }

        return new ProfessionalResource($professional->load(['user', 'services']));
    }
}
