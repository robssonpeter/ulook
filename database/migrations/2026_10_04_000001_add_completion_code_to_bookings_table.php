<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // 4-digit code the customer hands to the professional on delivery.
            $table->string('completion_code', 4)->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('completion_code');
        });

        // Backfill open bookings so they can still be completed.
        DB::table('bookings')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('completion_code')
            ->orderBy('id')
            ->each(function ($booking) {
                DB::table('bookings')->where('id', $booking->id)->update([
                    'completion_code' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['completion_code', 'completed_at']);
        });
    }
};
