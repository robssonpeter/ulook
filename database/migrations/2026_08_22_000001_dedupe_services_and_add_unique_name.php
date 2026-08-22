<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The seeder was run more than once with no uniqueness guard, so
        // `services` ended up with exact duplicate rows (same name). Merge
        // each group of duplicates into the lowest id before adding the
        // unique constraint, re-pointing every FK that references a
        // duplicate row at the row we're keeping.
        $duplicateGroups = DB::table('services')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateGroups as $name) {
            $ids = DB::table('services')
                ->where('name', $name)
                ->orderBy('id')
                ->pluck('id');

            $keepId = $ids->first();
            $dropIds = $ids->slice(1)->values();

            if ($dropIds->isEmpty()) {
                continue;
            }

            DB::table('professional_services')
                ->whereIn('service_id', $dropIds)
                ->update(['service_id' => $keepId]);

            DB::table('bookings')
                ->whereIn('service_id', $dropIds)
                ->update(['service_id' => $keepId]);

            DB::table('services')->whereIn('id', $dropIds)->delete();
        }

        Schema::table('services', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
