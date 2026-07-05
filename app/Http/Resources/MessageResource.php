<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'sender_id'   => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'booking_id'  => $this->booking_id,
            'content'     => $this->content,
            'read_at'     => $this->read_at?->toDateTimeString(),
            'created_at'  => $this->created_at?->toDateTimeString(),
            'sender_name' => $this->sender?->name,
            'is_mine'     => $request->user()?->id === $this->sender_id,
        ];
    }
}
