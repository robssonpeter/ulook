<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalSkill extends Model
{
    protected $fillable = ['professional_id', 'name'];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
