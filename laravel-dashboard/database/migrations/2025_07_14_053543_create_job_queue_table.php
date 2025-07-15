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
        Schema::create('job_queue', function (Blueprint $table) {
            $table->id('queue_id');
            $table->integer('job_id');
            $table->timestamp('queued_at')->useCurrent();
            $table->tinyInteger('status_code')->default(1);
            $table->timestamps();

            // Indexes for performance
            $table->index(['status_code', 'queued_at']);
            $table->index('job_id');

            // Foreign key constraint
            $table->foreign('job_id')->references('job_id')->on('job_postings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_queue');
    }
};
