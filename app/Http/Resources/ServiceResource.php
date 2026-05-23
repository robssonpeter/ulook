<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'professional_id' => $this->pivot ? (int) $this->pivot->professional_id : null,
            'name' => $this->pivot?->name ?? $this->name,
            'description' => $this->pivot ? $this->pivot->description : '',
            'price' => $this->pivot ? (float) $this->pivot->price : 0.0,
            'duration' => $this->pivot ? (int) $this->pivot->duration_minutes : 0,
        ];
    }
}
