<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        $pro = $this->professional;

        return [
            'id'                 => $this->id,
            'service_request_id' => $this->service_request_id,
            'professional_id'    => $this->professional_id,
            'professional'       => $pro ? [
                'id'               => $pro->id,
                'user_id'          => $pro->user_id,
                'name'             => $pro->user?->name,
                'location'         => $pro->location,
                'average_rating'   => round((float) ($pro->reviews_avg_rating ?? 0), 1),
                'reviews_count'    => (int) ($pro->reviews_count ?? 0),
                'profile_photo_url'=> $pro->user?->profile_photo_url,
            ] : null,
            'price_offered'      => (float) $this->price_offered,
            'message'            => $this->message,
            'status'             => $this->status,
            'created_at'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
