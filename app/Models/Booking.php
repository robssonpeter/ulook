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
        'completion_code',
        'completed_at',
        'type',
        'venue_type',
        'customer_address',
        'customer_latitude',
        'customer_longitude',
        'total_price',
        'deposit_amount',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    protected $hidden = ['completion_code'];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->completion_code ??= self::generateCompletionCode();
        });
    }

    public static function generateCompletionCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

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
