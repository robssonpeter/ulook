<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioPhoto extends Model
{
    protected $table = 'professional_portfolio_photos';

    protected $fillable = [
        'professional_id',
        'photo_url',
        'caption',
        'sort_order',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
