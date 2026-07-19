<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('venue_type')->nullable()->after('type'); // home|office|hotel|event
            $table->boolean('reminder_24h_sent')->default(false)->after('status');
            $table->boolean('reminder_1h_sent')->default(false)->after('reminder_24h_sent');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['venue_type', 'reminder_24h_sent', 'reminder_1h_sent']);
        });
    }
};
