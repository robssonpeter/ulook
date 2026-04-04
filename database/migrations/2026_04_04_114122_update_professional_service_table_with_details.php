<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('professional_service', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('duration')->default(30); // in minutes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professional_service', function (Blueprint $table) {
            $table->dropColumn(['description', 'price', 'duration']);
        });
    }
};
