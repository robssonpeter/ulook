<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->string('name');
            $table->string('unit')->default('pcs');   // ml, g, pcs, box, bottle …
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('reorder_at', 10, 2)->default(0);    // alert threshold
            $table->decimal('cost_per_unit', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
