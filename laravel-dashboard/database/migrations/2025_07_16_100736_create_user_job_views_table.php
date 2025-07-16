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
        Schema::create('user_job_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('job_id'); // Matches the job_id from job_postings table
            $table->timestamp('viewed_at');
            $table->timestamps();

            // Composite unique key to prevent duplicate views
            $table->unique(['user_id', 'job_id']);

            // Index for faster queries
            $table->index(['user_id', 'viewed_at']);

            // Foreign key constraint if users table exists
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_job_views');
    }
};
