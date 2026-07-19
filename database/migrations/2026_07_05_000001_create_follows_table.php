<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->timestamps();

            // A customer can follow a professional only once
            $table->unique(['user_id', 'professional_id'], 'follows_user_professional_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
