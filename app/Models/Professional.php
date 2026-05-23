<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'location',
        'latitude',
        'longitude',
        'price_range',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function professionalServices()
    {
        return $this->hasMany(ProfessionalService::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'professional_services')
            ->withPivot(['id', 'name', 'description', 'price', 'duration_minutes', 'is_active', 'professional_id'])
            ->withTimestamps();
    }

    /**
     * Reviews received by this professional.
     * professionals.user_id → bookings.professional_id → reviews.booking_id
     */
    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Booking::class,
            'professional_id', // FK on bookings (= professional's user_id)
            'booking_id',      // FK on reviews
            'user_id',         // local key on professionals
            'id'               // local key on bookings
        );
    }
}
