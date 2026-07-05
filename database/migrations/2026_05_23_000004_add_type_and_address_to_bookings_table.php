<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // 'booking' = customer goes to professional
            // 'request' = professional goes to customer
            $table->enum('type', ['booking', 'request'])->default('booking')->after('status');
            $table->string('customer_address')->nullable()->after('type');
            $table->decimal('customer_latitude', 10, 8)->nullable()->after('customer_address');
            $table->decimal('customer_longitude', 11, 8)->nullable()->after('customer_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['type', 'customer_address', 'customer_latitude', 'customer_longitude']);
        });
    }
};
