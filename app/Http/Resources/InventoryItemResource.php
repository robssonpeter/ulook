<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'unit'          => $this->unit,
            'current_stock' => (float) $this->current_stock,
            'reorder_at'    => (float) $this->reorder_at,
            'cost_per_unit' => (float) $this->cost_per_unit,
            'low_stock'     => $this->low_stock,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
