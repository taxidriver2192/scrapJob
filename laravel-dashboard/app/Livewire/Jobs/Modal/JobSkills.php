<?php

namespace App\Livewire\Jobs\Modal;

use Livewire\Component;

class JobSkills extends Component
{
    public $jobPosting;

    public function mount($jobPosting)
    {
        $this->jobPosting = $jobPosting;
    }

    // Helper methods for the view
    public function getSkills()
    {
        $skills = data_get($this->jobPosting, 'skills', []);
        
        // If skills is a string, try to parse it
        if (is_string($skills)) {
            // Try to decode JSON first
            $decoded = json_decode($skills, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            
            // If not JSON, try comma-separated values
            return array_filter(array_map('trim', explode(',', $skills)));
        }
        
        // If it's already an array, return as is
        return is_array($skills) ? $skills : [];
    }

    public function hasSkills()
    {
        return !empty($this->getSkills());
    }

    public function render()
    {
        return view('livewire.jobs.modal.job-skills');
    }
}
