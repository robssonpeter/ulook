<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalPost extends Model
{
    protected $fillable = [
        'professional_id',
        'type',
        'title',
        'body',
        'image_url',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
