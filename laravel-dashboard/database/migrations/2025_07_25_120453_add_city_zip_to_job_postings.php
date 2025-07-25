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
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('city')->nullable()->index();
            $table->string('zipcode', 10)->nullable()->index();
            // If you want to track ambiguity:
            // $table->boolean('zip_is_ambiguous')->default(false);
            // $table->json('zip_candidates')->nullable();
        });

        // For speed on lookups:
        Schema::table('addresses', function (Blueprint $table) {
            $table->index('postnrnavn');
            $table->index('postnr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['city', 'zipcode']);
        });
    }
};
