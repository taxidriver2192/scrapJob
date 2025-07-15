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
        Schema::create('job_ratings', function (Blueprint $table) {
            $table->id('rating_id');
            $table->integer('job_id');
            $table->integer('overall_score');
            $table->integer('location_score')->nullable();
            $table->integer('tech_score')->nullable();
            $table->integer('team_size_score')->nullable();
            $table->integer('leadership_score')->nullable();
            $table->json('criteria')->nullable();
            $table->string('rating_type')->default('manual');
            $table->timestamp('rated_at')->useCurrent();
            $table->timestamps();

            // Indexes for performance
            $table->index(['job_id', 'overall_score']);
            $table->index('rating_type');
            $table->index('rated_at');
            
            // Foreign key constraint
            $table->foreign('job_id')->references('job_id')->on('job_postings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_ratings');
    }
};
