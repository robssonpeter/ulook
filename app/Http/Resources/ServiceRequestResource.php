<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'customer_id'             => $this->customer_id,
            'service_id'              => $this->service_id,
            'service_name'            => $this->service?->name,
            'description'             => $this->description,
            'customer_address'        => $this->customer_address,
            'customer_latitude'       => (float) $this->customer_latitude,
            'customer_longitude'      => (float) $this->customer_longitude,
            'requested_date'          => $this->requested_date?->format('Y-m-d'),
            'requested_time'          => $this->requested_time,
            'radius_km'               => (float) $this->radius_km,
            'status'                  => $this->status,
            'customer'                => $this->whenLoaded('customer', fn () => [
                'id'    => $this->customer->id,
                'name'  => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'responses'               => ServiceRequestResponseResource::collection(
                $this->whenLoaded('responses')
            ),
            'responses_count'         => $this->whenCounted('responses'),
            'matched_professional_id' => $this->matched_professional_id,
            'matched_booking_id'      => $this->matched_booking_id,
            'distance_km'             => isset($this->distance)
                ? round((float) $this->distance, 1)
                : null,
            'created_at'              => $this->created_at?->toDateTimeString(),
        ];
    }
}
