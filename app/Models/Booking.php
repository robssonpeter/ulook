<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'professional_id',
        'service_id',
        'professional_service_id',
        'booking_date',
        'booking_time',
        'status',
        'total_price',
        'deposit_amount',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function professionalService()
    {
        return $this->belongsTo(ProfessionalService::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
