<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'booking_id'    => $this->booking_id,
            'rating'        => $this->rating,
            'comment'       => $this->comment,
            'reviewer_name' => $this->whenLoaded('booking', fn () => $this->booking?->customer?->name),
            'created_at'    => $this->created_at,
        ];
    }
}
