<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalWorkplace extends Model
{
    protected $fillable = [
        'professional_id',
        'name',
        'type',
        'area',
        'started_on',
        'ended_on',
        'is_public',
    ];

    protected $casts = [
        'started_on' => 'date:Y-m-d',
        'ended_on'   => 'date:Y-m-d',
        'is_public'  => 'boolean',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
