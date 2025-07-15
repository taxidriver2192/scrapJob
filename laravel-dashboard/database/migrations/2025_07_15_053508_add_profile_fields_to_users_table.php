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
        Schema::table('users', function (Blueprint $table) {
            // Basic Profile Info
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            
            // Professional Info
            $table->string('current_job_title')->nullable();
            $table->string('current_company')->nullable();
            $table->string('industry')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->json('skills')->nullable(); // Array of skills
            $table->text('career_summary')->nullable();
            
            // Job Preferences
            $table->string('preferred_job_type')->nullable(); // full-time, part-time, contract, freelance
            $table->boolean('remote_work_preference')->default(false);
            $table->string('preferred_location')->nullable();
            $table->integer('salary_expectation_min')->nullable();
            $table->integer('salary_expectation_max')->nullable();
            $table->string('currency')->default('USD');
            $table->boolean('willing_to_relocate')->default(false);
            
            // Education
            $table->string('highest_education')->nullable(); // high school, bachelor, master, phd, etc.
            $table->string('field_of_study')->nullable();
            $table->string('university')->nullable();
            $table->year('graduation_year')->nullable();
            $table->json('certifications')->nullable(); // Array of certifications
            
            // Contact Preferences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('job_alerts')->default(true);
            $table->json('preferred_contact_times')->nullable(); // Array of time preferences
            
            // Additional Info
            $table->json('languages')->nullable(); // Array of {language, proficiency}
            $table->string('availability')->nullable(); // immediately, 2 weeks, 1 month, etc.
            $table->text('additional_notes')->nullable();
            
            // Profile Completion
            $table->boolean('profile_completed')->default(false);
            $table->timestamp('profile_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'date_of_birth', 'bio', 'website', 'linkedin_url', 'github_url',
                'current_job_title', 'current_company', 'industry', 'years_of_experience',
                'skills', 'career_summary', 'preferred_job_type', 'remote_work_preference',
                'preferred_location', 'salary_expectation_min', 'salary_expectation_max',
                'currency', 'willing_to_relocate', 'highest_education', 'field_of_study',
                'university', 'graduation_year', 'certifications', 'email_notifications',
                'job_alerts', 'preferred_contact_times', 'languages', 'availability',
                'additional_notes', 'profile_completed', 'profile_updated_at'
            ]);
        });
    }
};
