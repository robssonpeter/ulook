<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'service_id',
        'description',
        'customer_address',
        'customer_latitude',
        'customer_longitude',
        'requested_date',
        'requested_time',
        'radius_km',
        'status',
        'matched_professional_id',
        'matched_booking_id',
    ];

    protected $casts = [
        'customer_latitude'  => 'decimal:8',
        'customer_longitude' => 'decimal:8',
        'radius_km'          => 'decimal:1',
        'requested_date'     => 'date:Y-m-d',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function responses()
    {
        return $this->hasMany(ServiceRequestResponse::class);
    }

    public function matchedBooking()
    {
        return $this->belongsTo(Booking::class, 'matched_booking_id');
    }
}
