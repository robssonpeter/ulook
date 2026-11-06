<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * YULUK Passport: a professional's portable career identity. Everything here
 * hangs off the professional, never off a station/salon, so it travels with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->uuid('passport_id')->nullable()->unique()->after('user_id');
        });

        DB::table('professionals')->whereNull('passport_id')->orderBy('id')->each(function ($professional) {
            DB::table('professionals')->where('id', $professional->id)->update(['passport_id' => (string) Str::uuid()]);
        });

        Schema::create('professional_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();
            $table->unique(['professional_id', 'name']);
        });

        Schema::create('professional_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->date('issued_on')->nullable();
            $table->string('file_url')->nullable();
            // pending until a YULUK admin checks the uploaded document.
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('professional_workplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('station'); // station | mobile
            $table->string('area')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable(); // null = works there now
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_workplaces');
        Schema::dropIfExists('professional_certifications');
        Schema::dropIfExists('professional_skills');
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropUnique(['passport_id']);
            $table->dropColumn('passport_id');
        });
    }
};
