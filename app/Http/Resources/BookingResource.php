<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'customer' => new UserResource($this->whenLoaded('customer')),
            'professional' => new UserResource($this->whenLoaded('professional')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'booking_date' => $this->booking_date,
            'booking_time' => $this->booking_time,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'deposit_amount' => $this->deposit_amount,
            'review' => new ReviewResource($this->whenLoaded('review')),
            'created_at' => $this->created_at,
        ];
    }
}
