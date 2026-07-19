<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalWorkingHours extends Model
{
    protected $fillable = [
        'professional_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
