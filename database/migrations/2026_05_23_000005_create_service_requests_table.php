<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('customer_address');
            $table->decimal('customer_latitude', 10, 8);
            $table->decimal('customer_longitude', 11, 8);
            $table->date('requested_date');
            $table->time('requested_time');
            $table->decimal('radius_km', 5, 1)->default(25); // search radius
            $table->enum('status', ['open', 'matched', 'cancelled'])->default('open');
            $table->foreignId('matched_professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('matched_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
