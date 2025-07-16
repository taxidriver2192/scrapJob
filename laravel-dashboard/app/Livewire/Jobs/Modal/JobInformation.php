<?php

namespace App\Livewire\Jobs\Modal;

use Livewire\Component;

class JobInformation extends Component
{
    public $jobPosting;

    public function mount($jobPosting)
    {
        $this->jobPosting = $jobPosting;
    }

    // Helper methods for the view
    public function getJobTitle()
    {
        return data_get($this->jobPosting, 'title', 'N/A');
    }

    public function getCompanyName()
    {
        return data_get($this->jobPosting, 'company.name', 'N/A');
    }

    public function getCompanyId()
    {
        return data_get($this->jobPosting, 'company.company_id');
    }

    public function getJobLocation()
    {
        return data_get($this->jobPosting, 'location', 'N/A');
    }

    public function getPostcode()
    {
        return data_get($this->jobPosting, 'postcode');
    }

    public function getPostedDate()
    {
        $postedDate = data_get($this->jobPosting, 'posted_date');
        return $postedDate ? $postedDate->format('M j, Y') : 'N/A';
    }

    public function getJobDescription()
    {
        return data_get($this->jobPosting, 'description');
    }

    public function getApplyUrl()
    {
        return data_get($this->jobPosting, 'apply_url');
    }

    public function render()
    {
        return view('livewire.jobs.modal.job-information');
    }
}
