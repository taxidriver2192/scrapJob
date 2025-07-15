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
        Schema::create('job_skills', function (Blueprint $table) {
            $table->id('job_skill_id');
            $table->integer('job_id'); // Match the type from job_postings table
            $table->unsignedBigInteger('skill_id'); // Match the type from skills table
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('job_id')->references('job_id')->on('job_postings');
            $table->foreign('skill_id')->references('skill_id')->on('skills');
            
            // Prevent duplicate job-skill combinations
            $table->unique(['job_id', 'skill_id']);
            
            // Indexes for better performance
            $table->index('job_id');
            $table->index('skill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_skills');
    }
};
