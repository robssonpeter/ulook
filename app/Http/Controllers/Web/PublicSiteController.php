<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionalResource;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public website at yuluk.me: landing page, browse, and profiles.
 * Read-only; booking itself happens in the mobile apps.
 */
class PublicSiteController extends Controller
{
    public function home(): Response
    {
        $featured = $this->baseQuery()
            ->orderByDesc('is_verified')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('professional_services_count')
            ->limit(8)
            ->get();

        return Inertia::render('Public/Home', [
            'categories' => $this->categories(),
            'featured' => $this->plain(ProfessionalResource::collection($featured)),
            'storeLinks' => $this->storeLinks(),
        ]);
    }

    public function professionals(Request $request): Response
    {
        $validated = $request->validate([
            'service' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = $this->baseQuery()
            ->orderByDesc('is_verified')
            ->orderByDesc('reviews_avg_rating')
            ->latest('professionals.id');

        if (! empty($validated['service'])) {
            $query->whereHas('professionalServices', fn (Builder $q) => $q
                ->where('service_id', $validated['service'])
                ->where('is_active', true));
        }

        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->where(fn (Builder $q) => $q
                ->where('location', 'like', $term)
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term))
                ->orWhereHas('services', fn (Builder $s) => $s->where('services.name', 'like', $term)));
        }

        $page = $query->paginate(12)->withQueryString();

        return Inertia::render('Public/Professionals', [
            'professionals' => $this->plain(ProfessionalResource::collection($page->getCollection())),
            'pagination' => [
                'current' => $page->currentPage(),
                'last' => $page->lastPage(),
                'total' => $page->total(),
                'prev' => $page->previousPageUrl(),
                'next' => $page->nextPageUrl(),
            ],
            'categories' => $this->categories(),
            'filters' => [
                'service' => isset($validated['service']) ? (int) $validated['service'] : null,
                'q' => $validated['q'] ?? '',
            ],
        ]);
    }

    public function professional(Request $request, Professional $professional): Response
    {
        $professional->load([
            'user',
            'services' => fn ($q) => $q->wherePivot('is_active', true),
            'portfolioPhotos',
            'workingHours',
        ])->loadAvg('reviews', 'rating')->loadCount(['reviews', 'followers']);

        return Inertia::render('Public/ProfessionalShow', [
            'professional' => $this->plain(new ProfessionalResource($professional)),
            // Catalogue rows (not services) are what a booking references.
            'bookable' => $professional->professionalServices()
                ->where('is_active', true)
                ->orderBy('price')
                ->get(['id', 'name', 'price', 'duration_minutes'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => (float) $s->price, 'duration' => $s->duration_minutes]),
            'isOwnProfile' => $request->user()?->id === $professional->user_id,
            'storeLinks' => $this->storeLinks(),
        ]);
    }

    /**
     * Plain arrays for page props. Inertia would otherwise turn nested
     * resource collections (e.g. services) into {"data": [...]} wrappers.
     */
    private function plain(JsonResource $resource): array
    {
        return json_decode(json_encode($resource), true);
    }

    private function baseQuery(): Builder
    {
        return Professional::query()
            ->with(['user', 'services'])
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'professionalServices']);
    }

    /** Services that at least one professional offers, with how many offer it. */
    private function categories(): array
    {
        return Service::query()
            ->withCount(['professionals as professionals_count' => fn ($q) => $q->where('professional_services.is_active', true)])
            ->orderByDesc('professionals_count')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Service $s) => ['id' => $s->id, 'name' => $s->name, 'count' => $s->professionals_count])
            ->all();
    }

    private function storeLinks(): array
    {
        return [
            'customer' => [
                'ios' => config('services.yuluk.customer_ios_url'),
                'android' => config('services.yuluk.customer_android_url'),
            ],
            'business' => [
                'ios' => config('services.yuluk.business_ios_url'),
                'android' => config('services.yuluk.business_android_url'),
            ],
        ];
    }
}
