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
        // Drop the old ai_job_ratings table
        if (Schema::hasTable('ai_job_ratings')) {
            Schema::drop('ai_job_ratings');
        }

        // Add AI rating fields into job_ratings table
        Schema::table('job_ratings', function (Blueprint $table) {
            // AI-specific columns
            $table->unsignedBigInteger('user_id')->after('job_id')->nullable()->index();
            $table->longText('prompt')->after('user_id')->nullable();
            $table->longText('response')->after('prompt')->nullable();
            $table->string('model', 50)->after('response')->default('gpt-3.5-turbo');
            $table->integer('prompt_tokens')->after('model')->nullable();
            $table->integer('completion_tokens')->after('prompt_tokens')->nullable();
            $table->integer('total_tokens')->after('completion_tokens')->nullable();
            $table->decimal('cost', 10, 6)->after('total_tokens')->nullable();
            $table->json('metadata')->after('cost')->nullable();

            // If ai ratings had no separate rated_at, keep using job_ratings.rated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_ratings', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'prompt',
                'response',
                'model',
                'prompt_tokens',
                'completion_tokens',
                'total_tokens',
                'cost',
                'metadata'
            ]);
        });

        // Note: restoring ai_job_ratings table is not supported in this rollback.
    }
};
