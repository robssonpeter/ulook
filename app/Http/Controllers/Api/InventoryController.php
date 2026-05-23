<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Models\Professional;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private function professional(Request $request): Professional
    {
        return Professional::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function index(Request $request)
    {
        $professional = $this->professional($request);
        $items = InventoryItem::where('professional_id', $professional->id)
            ->orderBy('name')
            ->get();

        return InventoryItemResource::collection($items);
    }

    public function store(Request $request)
    {
        $professional = $this->professional($request);

        $validated = $request->validate([
            'name'          => 'required|string|max:200',
            'unit'          => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'reorder_at'    => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
        ]);

        $item = InventoryItem::create([
            'professional_id' => $professional->id,
            ...$validated,
        ]);

        return (new InventoryItemResource($item))->response()->setStatusCode(201);
    }

    public function update(Request $request, $id)
    {
        $professional = $this->professional($request);
        $item = InventoryItem::where('professional_id', $professional->id)->findOrFail($id);

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:200',
            'unit'          => 'sometimes|string|max:50',
            'current_stock' => 'sometimes|numeric|min:0',
            'reorder_at'    => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
        ]);

        $item->update($validated);

        return new InventoryItemResource($item);
    }

    public function destroy(Request $request, $id)
    {
        $professional = $this->professional($request);
        $item = InventoryItem::where('professional_id', $professional->id)->findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item deleted.']);
    }

    /**
     * Adjust stock up (restock) or down (manual usage/write-off).
     * Body: { "adjustment": 50 }  — positive adds, negative deducts.
     */
    public function adjust(Request $request, $id)
    {
        $professional = $this->professional($request);
        $item = InventoryItem::where('professional_id', $professional->id)->findOrFail($id);

        $request->validate([
            'adjustment' => 'required|numeric',
        ]);

        $item->current_stock = max(0, $item->current_stock + $request->adjustment);
        $item->save();

        return new InventoryItemResource($item);
    }

    /** Items that are at or below their reorder threshold. */
    public function lowStock(Request $request)
    {
        $professional = $this->professional($request);

        $items = InventoryItem::where('professional_id', $professional->id)
            ->whereRaw('reorder_at > 0 AND current_stock <= reorder_at')
            ->orderBy('name')
            ->get();

        return InventoryItemResource::collection($items);
    }
}
