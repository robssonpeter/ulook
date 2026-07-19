<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionalResource;
use App\Models\Professional;
use App\Models\ProfessionalWorkingHours;
use Illuminate\Http\Request;

class ProfessionalController extends Controller
{
    public function index(Request $request)
    {
        $hasLocation = $request->has(['lat', 'lng']);

        if ($hasLocation) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;

            $query = Professional::with(['user', 'services'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->selectRaw(
                    'professionals.*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$lat, $lng, $lat]
                )
                ->orderBy('distance');

            if ($request->filled('radius')) {
                $query->havingRaw('distance <= ?', [(float) $request->radius]);
            }
        } else {
            $query = Professional::with(['user', 'services'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews');
        }

        if ($request->filled('service_id')) {
            $query->whereHas('professionalServices', function ($q) use ($request) {
                $q->where('service_id', $request->service_id)->where('is_active', true);
            });
        }

        if ($request->filled('min_price')) {
            $query->whereHas('professionalServices', function ($q) use ($request) {
                $q->where('price', '>=', (float) $request->min_price)->where('is_active', true);
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('professionalServices', function ($q) use ($request) {
                $q->where('price', '<=', (float) $request->max_price)->where('is_active', true);
            });
        }

        if ($request->filled('q')) {
            $search = '%' . $request->q . '%';
            $query->where(function ($q) use ($search) {
                $q->where('bio', 'like', $search)
                  ->orWhere('location', 'like', $search)
                  ->orWhere('category', 'like', $search)
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $search))
                  ->orWhereHas('services', fn ($sq) => $sq->where('name', 'like', $search));
            });
        }

        if ($request->filled('min_rating')) {
            $query->havingRaw('COALESCE(reviews_avg_rating, 0) >= ?', [(float) $request->min_rating]);
        }

        if ($request->filled('available_day')) {
            $query->whereHas('workingHours', function ($q) use ($request) {
                $q->where('day_of_week', (int) $request->available_day)
                  ->where('is_closed', false);
                if ($request->filled('available_time')) {
                    $q->whereRaw('? BETWEEN open_time AND close_time', [$request->available_time]);
                }
            });
        }

        if ($request->filled('sort_by') && $request->sort_by === 'rating') {
            $query->orderByDesc('reviews_avg_rating');
        }

        $professionals = $query->paginate(20);

        return ProfessionalResource::collection($professionals);
    }

    public function show($id)
    {
        $professional = Professional::with(['user', 'services', 'professionalServices.service', 'workingHours', 'portfolioPhotos'])
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'followers'])
            ->findOrFail($id);

        // Resolve the bearer token (this route is public, so no auth middleware).
        $viewer = auth('sanctum')->user();
        $professional->is_following = $viewer
            ? $professional->followers()->where('user_id', $viewer->id)->exists()
            : false;

        $professional->load(['posts' => fn ($q) => $q->latest()->limit(6)]);

        return new ProfessionalResource($professional);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (Professional::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Professional profile already exists.'], 400);
        }

        $validated = $request->validate([
            'bio'              => 'nullable|string',
            'location'         => 'required|string',
            'price_range'      => 'nullable|string',
            'years_experience' => 'nullable|integer|min:0|max:60',
            'services'         => 'required|array',
            'services.*'       => 'exists:services,id',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
        ]);

        $professional = Professional::create([
            'user_id'          => $user->id,
            'bio'              => $validated['bio'] ?? null,
            'location'         => $validated['location'],
            'price_range'      => $validated['price_range'] ?? null,
            'years_experience' => $validated['years_experience'] ?? null,
            'latitude'         => $validated['latitude'] ?? null,
            'longitude'        => $validated['longitude'] ?? null,
        ]);

        $professional->services()->attach($validated['services']);

        $user->update(['role' => 'professional']);

        return new ProfessionalResource(
            $professional->load(['user', 'services'])
                         ->loadAvg('reviews', 'rating')
                         ->loadCount('reviews')
        );
    }

    public function myProfile(Request $request)
    {
        $user = $request->user();
        $professional = Professional::with(['user', 'services', 'professionalServices.service', 'workingHours'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('user_id', $user->id)
            ->firstOrFail();

        return new ProfessionalResource($professional);
    }

    public function getWorkingHours(Request $request)
    {
        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();
        return response()->json(['data' => $professional->workingHours]);
    }

    public function saveWorkingHours(Request $request)
    {
        $request->validate([
            'hours'               => 'required|array',
            'hours.*.day_of_week' => 'required|integer|between:0,6',
            'hours.*.open_time'   => 'nullable|date_format:H:i',
            'hours.*.close_time'  => 'nullable|date_format:H:i',
            'hours.*.is_closed'   => 'required|boolean',
        ]);

        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();

        foreach ($request->hours as $hour) {
            ProfessionalWorkingHours::updateOrCreate(
                ['professional_id' => $professional->id, 'day_of_week' => $hour['day_of_week']],
                [
                    'open_time'  => $hour['is_closed'] ? null : ($hour['open_time'] ?? null),
                    'close_time' => $hour['is_closed'] ? null : ($hour['close_time'] ?? null),
                    'is_closed'  => $hour['is_closed'],
                ]
            );
        }

        return response()->json(['data' => $professional->workingHours()->orderBy('day_of_week')->get()]);
    }

    public function requestVerification(Request $request)
    {
        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();

        if ($professional->is_verified) {
            return response()->json(['message' => 'Already verified.'], 400);
        }

        if ($professional->verification_status === 'pending') {
            return response()->json(['message' => 'Verification request already submitted.'], 400);
        }

        $professional->update(['verification_status' => 'pending']);

        return response()->json(['message' => 'Verification request submitted successfully.', 'data' => new ProfessionalResource($professional->load(['user', 'services']))]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'bio'              => 'nullable|string',
            'location'         => 'required|string',
            'price_range'      => 'nullable|string',
            'years_experience' => 'nullable|integer|min:0|max:60',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
        ]);

        $professional->update($validated);

        return new ProfessionalResource(
            $professional->load(['user', 'services', 'professionalServices.service'])
                         ->loadAvg('reviews', 'rating')
                         ->loadCount('reviews')
        );
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
            'service_id'       => 'required|exists:services,id',
            'name'             => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'description'      => 'nullable|string',
        ]);

        $professionalService = $professional->professionalServices()->create($validated);

        return response()->json([
            'message' => 'Service added to catalog successfully.',
            'data'    => $professionalService->load('service'),
        ], 201);
    }

    public function updateService(Request $request, $id)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();
        $professionalService = $professional->professionalServices()->findOrFail($id);

        $validated = $request->validate([
            'name'             => 'nullable|string',
            'price'            => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'description'      => 'nullable|string',
            'is_active'        => 'nullable|boolean',
        ]);

        $professionalService->update($validated);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data'    => $professionalService->load('service'),
        ]);
    }

    public function toggleService(Request $request, $id)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();
        $professionalService = $professional->professionalServices()->findOrFail($id);

        $professionalService->update(['is_active' => ! $professionalService->is_active]);

        return response()->json([
            'message' => 'Service status toggled successfully.',
            'data'    => $professionalService,
        ]);
    }

    public function deleteService(Request $request, $id)
    {
        $user = $request->user();
        $professional = Professional::where('user_id', $user->id)->firstOrFail();
        $professionalService = $professional->professionalServices()->findOrFail($id);

        $professionalService->delete();

        return response()->json(['message' => 'Service deleted successfully.']);
    }
}
