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
            // Check if the old field exists before trying to drop it
            if (Schema::hasColumn('job_postings', 'job_post_closed')) {
                // Drop the old boolean field and its index (if it exists)
                $indexes = Schema::getConnection()->getDoctrineSchemaManager()
                    ->listTableIndexes('job_postings');

                if (isset($indexes['idx_job_post_closed'])) {
                    $table->dropIndex('idx_job_post_closed');
                }

                $table->dropColumn('job_post_closed');
            }

            // Add the new datetime field (if it doesn't exist)
            if (!Schema::hasColumn('job_postings', 'job_post_closed_date')) {
                $table->datetime('job_post_closed_date')->nullable()->comment('Date and time when the job posting was closed');
                $table->index('job_post_closed_date', 'idx_job_post_closed_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Check if the datetime field exists before trying to drop it
            if (Schema::hasColumn('job_postings', 'job_post_closed_date')) {
                $indexes = Schema::getConnection()->getDoctrineSchemaManager()
                    ->listTableIndexes('job_postings');

                if (isset($indexes['idx_job_post_closed_date'])) {
                    $table->dropIndex('idx_job_post_closed_date');
                }

                $table->dropColumn('job_post_closed_date');
            }

            // Restore the old boolean field (if it doesn't exist)
            if (!Schema::hasColumn('job_postings', 'job_post_closed')) {
                $table->boolean('job_post_closed')->default(false)->comment('Indicates if the job posting has been closed');
                $table->index('job_post_closed', 'idx_job_post_closed');
            }
        });
    }
};
