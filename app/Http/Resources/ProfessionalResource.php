<?php

namespace App\Http\Resources;

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
            'name' => $this->user ? $this->user->name : '',
            'bio' => $this->bio,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance' => $this->when(isset($this->distance), $this->distance),
            'price_range' => $this->price_range,
            'category' => $this->services->first() ? $this->services->first()->name : 'General',
            'is_verified' => $this->is_verified,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'professional_services' => $this->whenLoaded('professionalServices'),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
