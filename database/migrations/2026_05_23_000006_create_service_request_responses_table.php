<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->decimal('price_offered', 10, 2);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            // A professional can only respond once per request
            $table->unique(['service_request_id', 'professional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_responses');
    }
};
