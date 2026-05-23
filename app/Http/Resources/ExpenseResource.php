<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'category'     => $this->category,
            'amount'       => (float) $this->amount,
            'description'  => $this->description,
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'receipt_url'  => $this->receipt_url,
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];
    }
}
