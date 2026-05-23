<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'category',
        'amount',
        'description',
        'expense_date',
        'receipt_url',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date:Y-m-d',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
