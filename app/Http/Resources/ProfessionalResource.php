<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalResource extends JsonResource
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
            'user' => new UserResource($this->whenLoaded('user')),
            'bio' => $this->bio,
            'location' => $this->location,
            'price_range' => $this->price_range,
            'is_verified' => $this->is_verified,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at,
        ];
    }
}
