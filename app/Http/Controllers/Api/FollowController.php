<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionalResource;
use App\Models\Follow;
use App\Models\Professional;
use App\Models\ProfessionalPost;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function follow(Request $request, $id)
    {
        $professional = Professional::findOrFail($id);

        Follow::firstOrCreate([
            'user_id'         => $request->user()->id,
            'professional_id' => $professional->id,
        ]);

        return response()->json([
            'following'       => true,
            'followers_count' => $professional->followers()->count(),
        ]);
    }

    public function unfollow(Request $request, $id)
    {
        Follow::where('user_id', $request->user()->id)
            ->where('professional_id', $id)
            ->delete();

        return response()->json([
            'following'       => false,
            'followers_count' => Follow::where('professional_id', $id)->count(),
        ]);
    }

    /**
     * Professionals the authenticated user follows.
     */
    public function followed(Request $request)
    {
        $ids = Follow::where('user_id', $request->user()->id)->pluck('professional_id');

        $professionals = Professional::with(['user', 'services'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereIn('id', $ids)
            ->latest()
            ->get();

        return ProfessionalResource::collection($professionals);
    }

    /**
     * Activity feed (posts) from the professionals the user follows.
     * Optional ?type=offer|update|style filters the feed; omit for "All".
     */
    public function feed(Request $request)
    {
        $ids = Follow::where('user_id', $request->user()->id)->pluck('professional_id');

        $query = ProfessionalPost::with(['professional.user'])
            ->whereIn('professional_id', $ids)
            ->latest();

        if (in_array($request->type, ['offer', 'update', 'style'], true)) {
            $query->where('type', $request->type);
        }

        $posts = $query->paginate(20);

        $posts->getCollection()->transform(function (ProfessionalPost $post) {
            return [
                'id'                => $post->id,
                'type'              => $post->type,
                'title'             => $post->title,
                'body'              => $post->body,
                'image_url'         => $post->image_url,
                'created_at'        => $post->created_at,
                'professional_id'   => $post->professional_id,
                'professional_name' => $post->professional?->user?->name ?? 'Professional',
                'professional_photo'=> $post->professional?->user?->profile_photo_url,
                'location'          => $post->professional?->location,
            ];
        });

        return response()->json($posts);
    }
}
