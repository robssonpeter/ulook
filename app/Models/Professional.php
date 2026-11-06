<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'passport_id',
        'bio',
        'location',
        'latitude',
        'longitude',
        'price_range',
        'years_experience',
        'is_verified',
        'verification_status',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function booted(): void
    {
        static::creating(function (Professional $professional) {
            $professional->passport_id ??= (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function professionalServices()
    {
        return $this->hasMany(ProfessionalService::class);
    }

    public function followers()
    {
        return $this->hasMany(Follow::class);
    }

    public function posts()
    {
        return $this->hasMany(ProfessionalPost::class);
    }

    public function workingHours()
    {
        return $this->hasMany(ProfessionalWorkingHours::class)->orderBy('day_of_week');
    }

    public function portfolioPhotos()
    {
        return $this->hasMany(PortfolioPhoto::class)->orderBy('sort_order');
    }

    public function skills()
    {
        return $this->hasMany(ProfessionalSkill::class)->orderBy('name');
    }

    public function certifications()
    {
        return $this->hasMany(ProfessionalCertification::class)->latest('issued_on');
    }

    public function workplaces()
    {
        return $this->hasMany(ProfessionalWorkplace::class)->orderByDesc('started_on');
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
