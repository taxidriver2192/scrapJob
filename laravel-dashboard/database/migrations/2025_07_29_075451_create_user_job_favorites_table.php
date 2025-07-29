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
        Schema::create('user_job_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('job_id'); // Matches the job_id from job_postings table
            $table->timestamp('favorited_at')->useCurrent();
            $table->timestamps();

            // Composite unique key to prevent duplicate favorites
            $table->unique(['user_id', 'job_id']);

            // Index for faster queries
            $table->index(['user_id', 'favorited_at']);
            $table->index('job_id');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_id')->references('job_id')->on('job_postings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_job_favorites');
    }
};
