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
        Schema::table('professionals', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('location');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::rename('professional_service', 'professional_services');

        Schema::table('professional_services', function (Blueprint $table) {
            $table->string('name')->nullable()->after('service_id');
            if (Schema::hasColumn('professional_services', 'duration')) {
                $table->renameColumn('duration', 'duration_minutes');
            } else {
                $table->integer('duration_minutes')->nullable()->after('price');
            }
            $table->boolean('is_active')->default(true)->after('duration_minutes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professional_services', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropColumn(['is_active', 'name']);
            if (Schema::hasColumn('professional_services', 'duration_minutes')) {
                $table->renameColumn('duration_minutes', 'duration');
            }
        });

        Schema::rename('professional_services', 'professional_service');

        Schema::table('professionals', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
