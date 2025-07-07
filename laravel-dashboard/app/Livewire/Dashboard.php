<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JobPosting;
use App\Models\Company;
use App\Models\JobRating;
use App\Models\JobQueue;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $totalJobs;
    public $totalCompanies;
    public $totalRatings;
    public $queuedJobs;
    public $avgScore;
    public $recentJobs;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $this->totalJobs = JobPosting::count();
        $this->totalCompanies = Company::count();
        $this->totalRatings = JobRating::count();
        $this->queuedJobs = JobQueue::where('status_code', JobQueue::STATUS_PENDING)->count();
        
        $this->avgScore = JobRating::whereNotNull('overall_score')
            ->avg('overall_score');
        
        $this->recentJobs = JobPosting::with('company')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
