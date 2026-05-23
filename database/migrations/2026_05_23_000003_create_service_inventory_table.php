<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_service_id')->constrained('professional_services')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->decimal('quantity_used', 10, 2)->default(1); // amount consumed per booking
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inventory');
    }
};
