<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            // The three feed tabs in the app: Updates / Offers / Styles
            $table->enum('type', ['update', 'offer', 'style'])->default('update');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index(['professional_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_posts');
    }
};
