<?php

namespace App\Livewire\Jobs\Modal;

use Livewire\Component;

class JobSummary extends Component
{
    public $jobPosting;

    public function mount($jobPosting)
    {
        $this->jobPosting = $jobPosting;
    }

    // Helper methods for the view
    public function getBriefSummary()
    {
        return data_get($this->jobPosting, 'brief_summary_of_job', '');
    }

    public function hasSummary()
    {
        return !empty(trim($this->getBriefSummary()));
    }

    public function getSummaryWordCount()
    {
        if (!$this->hasSummary()) {
            return 0;
        }
        
        return str_word_count(strip_tags($this->getBriefSummary()));
    }

    public function render()
    {
        return view('livewire.jobs.modal.job-summary');
    }
}
