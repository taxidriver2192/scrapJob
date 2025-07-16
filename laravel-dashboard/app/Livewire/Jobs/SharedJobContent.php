<?php

namespace App\Livewire\Jobs;

use Livewire\Component;

class SharedJobContent extends Component
{
    public $jobPosting;
    public $rating;
    public $currentIndex;
    public $total;
    public $showNavigation;
    public $showBackButton;

    public function mount($jobPosting, $rating = null, $currentIndex = null, $total = null, $showNavigation = true, $showBackButton = false)
    {
        $this->jobPosting = $jobPosting;
        $this->rating = $rating;
        $this->currentIndex = $currentIndex;
        $this->total = $total;
        $this->showNavigation = $showNavigation;
        $this->showBackButton = $showBackButton;
    }

    public function previousRating()
    {
        $this->dispatch('previousRating');
    }

    public function nextRating()
    {
        $this->dispatch('nextRating');
    }

    public function goBackToDashboard()
    {
        $this->dispatch('goBackToDashboard');
    }

    public function canNavigatePrevious()
    {
        // This will be handled by the parent component
        return true;
    }

    public function canNavigateNext()
    {
        // This will be handled by the parent component
        return true;
    }

    public function render()
    {
        return view('livewire.jobs.shared-job-content');
    }
}
