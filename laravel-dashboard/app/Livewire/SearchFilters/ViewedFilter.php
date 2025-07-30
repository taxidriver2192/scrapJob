<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewedFilter extends Component
{
    public string $status = '';
    public array $options = [];

    public function mount($status = '')
    {
        $this->status = $status;
        $this->loadOptions();
    }

    private function loadOptions()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $totalJobs = DB::table('job_postings')->whereNull('job_post_closed_date')->count();
            
            $viewedCount = DB::table('job_postings')
                ->join('user_job_views', 'job_postings.job_id', '=', 'user_job_views.job_id')
                ->where('user_job_views.user_id', $userId)
                ->whereNull('job_postings.job_post_closed_date')
                ->count();
            
            $notViewedCount = $totalJobs - $viewedCount;

            $this->options = [
                '' => "All Jobs ($totalJobs)",
                'viewed' => "Viewed ($viewedCount)",
                'not_viewed' => "Not Viewed ($notViewedCount)",
            ];
        } else {
            $totalJobs = DB::table('job_postings')->whereNull('job_post_closed_date')->count();
            $this->options = [
                '' => "All Jobs ($totalJobs)",
                'viewed' => "Viewed (0)",
                'not_viewed' => "Not Viewed (0)",
            ];
        }
    }

    public function updatedStatus($value)
    {
        $this->dispatch('viewedFilterUpdated', status: $value);
    }

    public function render()
    {
        return view('livewire.search-filters.viewed-filter');
    }
}
