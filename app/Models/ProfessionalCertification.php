<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalCertification extends Model
{
    protected $fillable = [
        'professional_id',
        'title',
        'issuer',
        'issued_on',
        'file_url',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'issued_on'   => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
