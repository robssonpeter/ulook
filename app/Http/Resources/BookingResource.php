<?php

namespace App\Http\Resources;

use App\Models\Professional;
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
        // Try to get the professional profile if possible
        $professionalProfile = null;
        if ($this->relationLoaded('professional') && $this->professional_id) {
            $professionalProfile = Professional::with('services')->where('user_id', $this->professional_id)->first();
        }

        // When loading the service, we want to find the pivot data from the professional
        $serviceData = $this->whenLoaded('service');
        if ($serviceData && $professionalProfile) {
            $professionalService = $professionalProfile->services->find($this->service_id);
            if ($professionalService) {
                $serviceData = $professionalService;
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->customer_id,
            'professional_id' => $this->professional_id,
            'service_id' => $this->service_id,
            'professional_service_id' => $this->professional_service_id,
            'booking_date' => $this->booking_date,
            'booking_time' => $this->booking_time,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'deposit_amount' => $this->deposit_amount,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'professional' => $professionalProfile ? new ProfessionalResource($professionalProfile) : new UserResource($this->whenLoaded('professional')),
            'service' => $serviceData ? new ServiceResource($serviceData) : null,
            'professional_service' => $this->whenLoaded('professionalService'),
            'review' => new ReviewResource($this->whenLoaded('review')),
            'created_at' => $this->created_at,
        ];
    }
}
