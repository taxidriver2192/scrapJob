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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->integer('job_id')->autoIncrement()->primary();
            $table->bigInteger('linkedin_job_id');
            $table->string('title', 500);
            $table->integer('company_id');
            $table->string('location', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('apply_url', 2048)->nullable();
            $table->date('posted_date')->nullable();
            $table->integer('applicants')->nullable()->comment('Number of applicants');
            $table->string('work_type', 50)->nullable()->comment('Remote, Hybrid, or On-site work type');
            $table->json('skills')->nullable()->comment('List of required skills');
            $table->string('openai_adresse', 500)->nullable()->comment('AI-extracted standardized address');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Unique key and indexes to match the SQL backup exactly
            $table->unique('linkedin_job_id');
            $table->index('linkedin_job_id', 'idx_linkedin_job_id');
            $table->index('company_id', 'idx_company_id');
            $table->index('posted_date', 'idx_posted_date');
            $table->index('location', 'idx_location');
            $table->index('title', 'idx_title');

            // Foreign key constraint
            $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
