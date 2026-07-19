<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->user ? $this->user->name : '',
            'bio'                   => $this->bio,
            'location'              => $this->location,
            'latitude'              => $this->latitude,
            'longitude'             => $this->longitude,
            'distance'              => $this->when(isset($this->distance), $this->distance),
            'price_range'           => $this->price_range,
            'years_experience'      => $this->years_experience,
            'category'              => $this->services->isNotEmpty() ? $this->services->first()->name : 'General',
            'is_verified'           => $this->is_verified,
            'verification_status'   => $this->verification_status,
            'average_rating'        => round((float) ($this->reviews_avg_rating ?? 0), 1),
            'reviews_count'         => (int) ($this->reviews_count ?? 0),
            'followers_count'       => (int) ($this->followers_count ?? 0),
            'is_following'          => (bool) ($this->is_following ?? false),
            'profile_photo_url'     => $this->user?->profile_photo_url,
            'services'              => ServiceResource::collection($this->whenLoaded('services')),
            'professional_services' => $this->whenLoaded('professionalServices'),
            'working_hours'         => $this->whenLoaded('workingHours'),
            'portfolio_photos'      => $this->whenLoaded('portfolioPhotos'),
            'posts'                 => $this->whenLoaded('posts'),
            'user'                  => new UserResource($this->whenLoaded('user')),
            'created_at'            => $this->created_at,
        ];
    }
}
