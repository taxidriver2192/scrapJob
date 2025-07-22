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
            // Drop the old boolean field and its index
            $table->dropIndex('idx_job_post_closed');
            $table->dropColumn('job_post_closed');
            
            // Add the new datetime field
            $table->datetime('job_post_closed_date')->nullable()->comment('Date and time when the job posting was closed');
            $table->index('job_post_closed_date', 'idx_job_post_closed_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Drop the datetime field and its index
            $table->dropIndex('idx_job_post_closed_date');
            $table->dropColumn('job_post_closed_date');
            
            // Restore the old boolean field
            $table->boolean('job_post_closed')->default(false)->comment('Indicates if the job posting has been closed');
            $table->index('job_post_closed', 'idx_job_post_closed');
        });
    }
};
