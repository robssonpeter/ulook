<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\ProfessionalPost;
use Illuminate\Http\Request;

class ProfessionalPostController extends Controller
{
    private function professionalFor(Request $request): Professional
    {
        return Professional::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function index(Request $request)
    {
        $professional = $this->professionalFor($request);

        return response()->json([
            'data' => $professional->posts()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $professional = $this->professionalFor($request);

        $validated = $request->validate([
            'type'      => 'required|in:update,offer,style',
            'title'     => 'required|string|max:255',
            'body'      => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $post = $professional->posts()->create($validated);

        return response()->json([
            'message' => 'Post published.',
            'data'    => $post,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $professional = $this->professionalFor($request);
        $professional->posts()->findOrFail($id)->delete();

        return response()->json(['message' => 'Post deleted.']);
    }
}
