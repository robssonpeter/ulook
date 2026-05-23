<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'name',
        'unit',
        'current_stock',
        'reorder_at',
        'cost_per_unit',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'reorder_at'    => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function professionalServices()
    {
        return $this->belongsToMany(
            ProfessionalService::class,
            'service_inventory',
            'inventory_item_id',
            'professional_service_id'
        )->withPivot('quantity_used')->withTimestamps();
    }

    public function getLowStockAttribute(): bool
    {
        return $this->reorder_at > 0 && $this->current_stock <= $this->reorder_at;
    }
}
