<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function professionals()
    {
        return $this->belongsToMany(Professional::class, 'professional_services')
            ->withPivot(['id', 'name', 'description', 'price', 'duration_minutes', 'is_active'])
            ->withTimestamps();
    }

    public function professionalServices()
    {
        return $this->hasMany(ProfessionalService::class);
    }
}
