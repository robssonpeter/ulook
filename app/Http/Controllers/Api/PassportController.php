<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Professional;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * YULUK Passport: the professional's portable career identity.
 *
 * Reputation only counts verified YULUK work: bookings the professional closed
 * with the customer's completion code (status completed + completed_at set),
 * and the reviews left on those bookings.
 */
class PassportController extends Controller
{
    /** Completed-job counts that earn a line on the career timeline. */
    private const MILESTONES = [10, 50, 100, 250, 500, 1000, 2500, 5000];

    /**
     * Words in a service name that count a completed job towards a skill.
     * Skills not listed here match on their own name.
     */
    private const SKILL_KEYWORDS = [
        'barbering'    => ['barber', 'haircut', 'fade', 'shave', 'beard', 'grooming'],
        'hair styling' => ['hair', 'blow', 'relax', 'perm', 'wig', 'weave', 'style'],
        'braiding'     => ['braid', 'twist', 'cornrow', 'locs', 'dread', 'knotless'],
        'makeup'       => ['makeup', 'make-up', 'make up', 'glam'],
        'nails'        => ['nail', 'manicure', 'pedicure', 'gel', 'acrylic'],
        'lashes'       => ['lash', 'brow'],
        'facials'      => ['facial', 'skin', 'peel'],
        'massage'      => ['massage'],
        'bridal'       => ['bridal', 'bride', 'wedding'],
        'spa'          => ['spa'],
        'kids hair'    => ['kid', 'child'],
    ];

    public const SKILL_SUGGESTIONS = [
        'Barbering', 'Hair Styling', 'Braiding', 'Makeup', 'Nails',
        'Lashes', 'Facials', 'Massage', 'Bridal', 'Spa', 'Kids Hair',
    ];

    // ── Read ────────────────────────────────────────────────────────────────

    /** Public Passport shown to customers before they book. */
    public function show($id)
    {
        $professional = Professional::findOrFail($id);

        return response()->json(['data' => $this->build($professional, false)]);
    }

    /** The professional's own Passport, including pending and private items. */
    public function mine(Request $request)
    {
        return response()->json(['data' => $this->build($this->owner($request), true)]);
    }

    private function build(Professional $professional, bool $isOwner): array
    {
        $professional->load([
            'user',
            'skills',
            'certifications',
            'workplaces',
            'portfolioPhotos',
            'workingHours',
            'professionalServices' => fn ($q) => $q->where('is_active', true)->with('service'),
        ]);

        $verifiedJobs = Booking::query()
            ->leftJoin('professional_services', 'professional_services.id', '=', 'bookings.professional_service_id')
            ->leftJoin('services', 'services.id', '=', 'bookings.service_id')
            ->where('bookings.professional_id', $professional->user_id)
            ->where('bookings.status', 'completed')
            ->whereNotNull('bookings.completed_at')
            ->orderBy('bookings.completed_at')
            ->get([
                'bookings.completed_at',
                'professional_services.name as custom_name',
                'services.name as service_name',
            ]);

        $reviews = Review::whereHas('booking', fn ($q) => $q
                ->where('professional_id', $professional->user_id)
                ->where('status', 'completed'))
            ->with('booking.customer');

        $reviewsCount = (clone $reviews)->count();
        $averageRating = round((float) (clone $reviews)->avg('rating'), 1);

        $certifications = $professional->certifications
            ->filter(fn ($c) => $isOwner || $c->status === 'verified')
            ->values();

        $workplaces = $professional->workplaces
            ->filter(fn ($w) => $isOwner || $w->is_public || $w->ended_on === null)
            ->values();

        $skills = $professional->skills->map(function ($skill) use ($verifiedJobs) {
            $jobs = $this->jobsForSkill($skill->name, $verifiedJobs);

            return [
                'id'             => $skill->id,
                'name'           => $skill->name,
                'jobs_completed' => $jobs,
                'is_verified'    => $jobs > 0,
            ];
        })->values();

        $joinedAt = $professional->created_at ?? now();

        return [
            'passport_id'         => $professional->passport_id,
            'professional_id'     => $professional->id,
            'name'                => $professional->user?->name ?? '',
            'profile_photo_url'   => $professional->user?->profile_photo_url,
            'bio'                 => $professional->bio,
            'location'            => $professional->location,
            'is_verified'         => (bool) $professional->is_verified,
            'verification_status' => $professional->verification_status,
            'phone_verified'      => ! empty($professional->user?->phone),
            'email_verified'      => $professional->user?->email_verified_at !== null,
            'member_since'        => $joinedAt->toDateString(),
            'years_experience'    => $professional->years_experience,
            'stats' => [
                'services_completed'      => $verifiedJobs->count(),
                'reviews_count'           => $reviewsCount,
                'average_rating'          => $averageRating,
                'certifications_verified' => $professional->certifications->where('status', 'verified')->count(),
                'skills_count'            => $skills->count(),
            ],
            'skills'         => $skills,
            'certifications' => $certifications->map(fn ($c) => $this->certificationData($c, $isOwner))->values(),
            'workplaces'     => $workplaces->map(fn ($w) => $this->workplaceData($w))->values(),
            'portfolio'      => $professional->portfolioPhotos->map(fn ($p) => [
                'id'        => $p->id,
                'photo_url' => $p->photo_url,
                'caption'   => $p->caption,
            ])->values(),
            'services' => $professional->professionalServices->map(fn ($s) => [
                'id'               => $s->id,
                'name'             => $s->name ?: $s->service?->name,
                'price'            => (float) $s->price,
                'duration_minutes' => $s->duration_minutes,
            ])->values(),
            'working_days'   => $professional->workingHours->where('is_closed', false)->pluck('day_of_week')->values(),
            'recent_reviews' => ReviewResource::collection($reviews->latest()->limit(5)->get()),
            'timeline'       => $this->timeline($joinedAt, $verifiedJobs, $certifications, $workplaces),
        ];
    }

    private function jobsForSkill(string $skill, $verifiedJobs): int
    {
        $key = Str::lower(trim($skill));
        $keywords = self::SKILL_KEYWORDS[$key] ?? [$key];

        return $verifiedJobs->filter(function ($job) use ($keywords) {
            $name = Str::lower(($job->custom_name ?? '').' '.($job->service_name ?? ''));

            return Str::contains($name, $keywords);
        })->count();
    }

    /** Career history, newest first, built only from verified records. */
    private function timeline(Carbon $joinedAt, $verifiedJobs, $certifications, $workplaces): array
    {
        $events = [[
            'date'  => $joinedAt->toDateString(),
            'type'  => 'joined',
            'title' => 'Joined YULUK',
        ]];

        foreach (self::MILESTONES as $count) {
            $job = $verifiedJobs->get($count - 1);
            if (! $job) {
                break;
            }
            $events[] = [
                'date'  => Carbon::parse($job->completed_at)->toDateString(),
                'type'  => 'milestone',
                'title' => number_format($count).' verified services completed',
            ];
        }

        foreach ($certifications->where('status', 'verified') as $cert) {
            $events[] = [
                'date'  => ($cert->issued_on ?? $cert->reviewed_at ?? $cert->created_at)->toDateString(),
                'type'  => 'certification',
                'title' => 'Earned '.$cert->title,
            ];
        }

        foreach ($workplaces as $place) {
            if (! $place->started_on) {
                continue;
            }
            $events[] = [
                'date'  => $place->started_on->toDateString(),
                'type'  => 'workplace',
                'title' => $place->type === 'mobile'
                    ? 'Started mobile service'.($place->area ? ' in '.$place->area : '')
                    : 'Started at '.$place->name.($place->area ? ', '.$place->area : ''),
            ];
        }

        usort($events, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $events;
    }

    private function certificationData($cert, bool $isOwner): array
    {
        return [
            'id'        => $cert->id,
            'title'     => $cert->title,
            'issuer'    => $cert->issuer,
            'issued_on' => $cert->issued_on?->toDateString(),
            'status'    => $cert->status,
            // The document itself stays private to the professional.
            'file_url'  => $isOwner ? $cert->file_url : null,
        ];
    }

    private function workplaceData($place): array
    {
        return [
            'id'         => $place->id,
            'name'       => $place->name,
            'type'       => $place->type,
            'area'       => $place->area,
            'started_on' => $place->started_on?->toDateString(),
            'ended_on'   => $place->ended_on?->toDateString(),
            'is_current' => $place->ended_on === null,
            'is_public'  => $place->is_public,
        ];
    }

    // ── Skills ──────────────────────────────────────────────────────────────

    public function skillSuggestions()
    {
        return response()->json(['data' => self::SKILL_SUGGESTIONS]);
    }

    public function storeSkill(Request $request)
    {
        $professional = $this->owner($request);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('professional_skills')->where('professional_id', $professional->id),
            ],
        ]);

        $skill = $professional->skills()->create(['name' => trim($validated['name'])]);

        return response()->json(['data' => $skill], 201);
    }

    public function destroySkill(Request $request, $id)
    {
        $this->owner($request)->skills()->findOrFail($id)->delete();

        return response()->json(['message' => 'Skill removed.']);
    }

    // ── Certifications ──────────────────────────────────────────────────────

    public function storeCertification(Request $request)
    {
        $professional = $this->owner($request);

        $validated = $request->validate([
            'title'     => 'required|string|max:150',
            'issuer'    => 'nullable|string|max:150',
            'issued_on' => 'nullable|date|before_or_equal:today',
            'file'      => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $fileUrl = $request->hasFile('file')
            ? Storage::url($request->file('file')->store('certifications', 'public'))
            : null;

        $cert = $professional->certifications()->create([
            'title'     => $validated['title'],
            'issuer'    => $validated['issuer'] ?? null,
            'issued_on' => $validated['issued_on'] ?? null,
            'file_url'  => $fileUrl,
            'status'    => 'pending',
        ]);

        return response()->json(['data' => $this->certificationData($cert, true)], 201);
    }

    public function destroyCertification(Request $request, $id)
    {
        $cert = $this->owner($request)->certifications()->findOrFail($id);

        if ($cert->file_url) {
            Storage::disk('public')->delete(Str::after($cert->file_url, '/storage/'));
        }
        $cert->delete();

        return response()->json(['message' => 'Certification removed.']);
    }

    // ── Workplaces (stations + mobile service) ──────────────────────────────

    public function storeWorkplace(Request $request)
    {
        $professional = $this->owner($request);
        $place = $professional->workplaces()->create($this->validateWorkplace($request));

        return response()->json(['data' => $this->workplaceData($place)], 201);
    }

    public function updateWorkplace(Request $request, $id)
    {
        $place = $this->owner($request)->workplaces()->findOrFail($id);
        $place->update($this->validateWorkplace($request, partial: true));

        return response()->json(['data' => $this->workplaceData($place->fresh())]);
    }

    public function destroyWorkplace(Request $request, $id)
    {
        $this->owner($request)->workplaces()->findOrFail($id)->delete();

        return response()->json(['message' => 'Workplace removed.']);
    }

    private function validateWorkplace(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name'       => [$required, 'string', 'max:150'],
            'type'       => [$required, Rule::in(['station', 'mobile'])],
            'area'       => ['nullable', 'string', 'max:150'],
            'started_on' => ['nullable', 'date', 'before_or_equal:today'],
            'ended_on'   => array_filter([
                'nullable', 'date', 'before_or_equal:today',
                $request->filled('started_on') ? 'after_or_equal:started_on' : null,
            ]),
            'is_public'  => ['sometimes', 'boolean'],
        ]);
    }

    private function owner(Request $request): Professional
    {
        return Professional::where('user_id', $request->user()->id)->firstOrFail();
    }
}
