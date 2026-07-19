<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortfolioPhoto;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function index($professionalId)
    {
        $professional = Professional::findOrFail($professionalId);
        return response()->json(['data' => $professional->portfolioPhotos]);
    }

    public function myPortfolio(Request $request)
    {
        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();
        return response()->json(['data' => $professional->portfolioPhotos()->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'photo'      => 'required|image|max:5120',
            'caption'    => 'nullable|string|max:200',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();

        $path = $request->file('photo')->store('portfolio', 'public');
        $url  = Storage::url($path);

        $photo = $professional->portfolioPhotos()->create([
            'photo_url'  => $url,
            'caption'    => $request->caption,
            'sort_order' => $request->sort_order ?? $professional->portfolioPhotos()->count(),
        ]);

        return response()->json(['data' => $photo], 201);
    }

    public function destroy(Request $request, $id)
    {
        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();
        $photo = $professional->portfolioPhotos()->findOrFail($id);

        // Remove the file from storage
        $path = str_replace('/storage/', 'public/', $photo->photo_url);
        Storage::delete($path);

        $photo->delete();

        return response()->json(['message' => 'Photo deleted.']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'    => 'required|array',
            'order.*' => 'integer|exists:professional_portfolio_photos,id',
        ]);

        $professional = Professional::where('user_id', $request->user()->id)->firstOrFail();

        foreach ($request->order as $position => $photoId) {
            $professional->portfolioPhotos()->where('id', $photoId)->update(['sort_order' => $position]);
        }

        return response()->json(['message' => 'Order updated.']);
    }
}
