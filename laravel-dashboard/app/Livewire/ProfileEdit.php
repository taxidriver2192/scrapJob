<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ProfileEdit extends Component
{
    // Basic Profile Info
    public $name = '';
    public $email = '';
    public $phone = '';
    public $date_of_birth = '';
    public $bio = '';
    public $website = '';
    public $linkedin_url = '';
    public $github_url = '';

    // Professional Info
    public $current_job_title = '';
    public $current_company = '';
    public $industry = '';
    public $years_of_experience = '';
    public $skills = [];
    public $career_summary = '';

    // Job Preferences
    public $preferred_job_type = '';
    public $remote_work_preference = false;
    public $preferred_location = '';
    public $salary_expectation_min = '';
    public $salary_expectation_max = '';
    public $currency = 'USD';
    public $willing_to_relocate = false;
    public $open_to_management = false;

    // Education
    public $highest_education = '';
    public $field_of_study = '';
    public $university = '';
    public $graduation_year = '';
    public $certifications = [];

    // Contact Preferences
    public $email_notifications = true;
    public $job_alerts = true;
    public $preferred_contact_times = [];

    // Additional Info
    public $languages = [];
    public $availability = '';
    public $additional_notes = '';

    // Temporary fields for adding items
    public $newSkill = '';
    public $newCertification = '';
    public $newLanguage = '';
    public $newLanguageProficiency = '';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'bio' => 'nullable|string',
            'website' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'years_of_experience' => 'nullable|integer|min:0',
            'career_summary' => 'nullable|string',
            'preferred_job_type' => 'nullable|string',
            'preferred_location' => 'nullable|string|max:255',
            'salary_expectation_min' => 'nullable|integer|min:0',
            'salary_expectation_max' => 'nullable|integer|min:0',
            'currency' => 'required|string|max:3',
            'highest_education' => 'nullable|string',
            'field_of_study' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|integer|min:1950|max:' . (date('Y') + 10),
            'availability' => 'nullable|string',
            'additional_notes' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $user = Auth::user();

        // Load user data into component properties
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->date_of_birth = $user->date_of_birth?->format('Y-m-d') ?? '';
        $this->bio = $user->bio ?? '';
        $this->website = $user->website ?? '';
        $this->linkedin_url = $user->linkedin_url ?? '';
        $this->github_url = $user->github_url ?? '';

        $this->current_job_title = $user->current_job_title ?? '';
        $this->current_company = $user->current_company ?? '';
        $this->industry = $user->industry ?? '';
        $this->years_of_experience = $user->years_of_experience ?? '';

        // Handle skills format migration
        $userSkills = $user->skills ?? [];
        $this->skills = [];
        foreach ($userSkills as $skill) {
            if (is_string($skill)) {
                // Convert old format to new format
                $this->skills[] = [
                    'name' => $skill,
                    'rating' => 5 // Default rating for existing skills
                ];
            } elseif (is_array($skill) && isset($skill['name'])) {
                // Already in new format
                $this->skills[] = $skill;
            }
        }

        $this->career_summary = $user->career_summary ?? '';

        $this->preferred_job_type = $user->preferred_job_type ?? '';
        $this->remote_work_preference = $user->remote_work_preference ?? false;
        $this->preferred_location = $user->preferred_location ?? '';
        $this->salary_expectation_min = $user->salary_expectation_min ?? '';
        $this->salary_expectation_max = $user->salary_expectation_max ?? '';
        $this->currency = $user->currency ?? 'USD';
        $this->willing_to_relocate = $user->willing_to_relocate ?? false;
        $this->open_to_management = $user->open_to_management ?? false;

        $this->highest_education = $user->highest_education ?? '';
        $this->field_of_study = $user->field_of_study ?? '';
        $this->university = $user->university ?? '';
        $this->graduation_year = $user->graduation_year ?? '';
        $this->certifications = $user->certifications ?? [];

        $this->email_notifications = $user->email_notifications ?? true;
        $this->job_alerts = $user->job_alerts ?? true;
        $this->preferred_contact_times = $user->preferred_contact_times ?? [];

        $this->languages = $user->languages ?? [];
        $this->availability = $user->availability ?? '';
        $this->additional_notes = $user->additional_notes ?? '';
    }

    public function addSkill()
    {
        if (!empty($this->newSkill)) {
            $this->skills[] = [
                'name' => trim($this->newSkill),
                'rating' => 5 // Default rating
            ];
            $this->newSkill = '';
        }
    }

    public function removeSkill($index)
    {
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function updateSkillRating($index, $rating)
    {
        if (isset($this->skills[$index])) {
            $this->skills[$index]['rating'] = (int) $rating;
        }
    }

    public function addCertification()
    {
        if (!empty($this->newCertification)) {
            $this->certifications[] = trim($this->newCertification);
            $this->newCertification = '';
        }
    }

    public function removeCertification($index)
    {
        unset($this->certifications[$index]);
        $this->certifications = array_values($this->certifications);
    }

    public function addLanguage()
    {
        if (!empty($this->newLanguage) && !empty($this->newLanguageProficiency)) {
            $this->languages[] = [
                'language' => trim($this->newLanguage),
                'proficiency' => trim($this->newLanguageProficiency)
            ];
            $this->newLanguage = '';
            $this->newLanguageProficiency = '';
        }
    }

    public function removeLanguage($index)
    {
        unset($this->languages[$index]);
        $this->languages = array_values($this->languages);
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth ?: null,
            'bio' => $this->bio,
            'website' => $this->website,
            'linkedin_url' => $this->linkedin_url,
            'github_url' => $this->github_url,
            'current_job_title' => $this->current_job_title,
            'current_company' => $this->current_company,
            'industry' => $this->industry,
            'years_of_experience' => $this->years_of_experience ?: null,
            'skills' => $this->skills,
            'career_summary' => $this->career_summary,
            'preferred_job_type' => $this->preferred_job_type,
            'remote_work_preference' => $this->remote_work_preference,
            'preferred_location' => $this->preferred_location,
            'salary_expectation_min' => $this->salary_expectation_min ?: null,
            'salary_expectation_max' => $this->salary_expectation_max ?: null,
            'currency' => $this->currency,
            'willing_to_relocate' => $this->willing_to_relocate,
            'open_to_management' => $this->open_to_management,
            'highest_education' => $this->highest_education,
            'field_of_study' => $this->field_of_study,
            'university' => $this->university,
            'graduation_year' => $this->graduation_year ?: null,
            'certifications' => $this->certifications,
            'email_notifications' => $this->email_notifications,
            'job_alerts' => $this->job_alerts,
            'preferred_contact_times' => $this->preferred_contact_times,
            'languages' => $this->languages,
            'availability' => $this->availability,
            'additional_notes' => $this->additional_notes,
            'profile_completed' => $this->isProfileComplete(),
            'profile_updated_at' => now(),
        ]);

        session()->flash('success', 'Profile updated successfully!');
    }

    private function isProfileComplete()
    {
        // Basic check for profile completion
        return !empty($this->name) &&
               !empty($this->email) &&
               !empty($this->bio) &&
               !empty($this->current_job_title) &&
               !empty($this->skills);
    }

    public function render()
    {
        return view('livewire.profile-edit');
    }
}
