<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'professional_id',
        'price_offered',
        'message',
        'status',
    ];

    protected $casts = [
        'price_offered' => 'decimal:2',
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
