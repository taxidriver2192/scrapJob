<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class RatingFilter extends Component
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
        $totalJobs = DB::table('job_postings')->whereNull('job_post_closed_date')->count();
        
        $ratedCount = DB::table('job_postings')
            ->join('job_ratings', 'job_postings.job_id', '=', 'job_ratings.job_id')
            ->whereNull('job_postings.job_post_closed_date')
            ->distinct('job_postings.job_id')
            ->count();
        
        $notRatedCount = $totalJobs - $ratedCount;

        $this->options = [
            '' => "All Jobs ($totalJobs)",
            'rated' => "Rated ($ratedCount)",
            'not_rated' => "Not Rated ($notRatedCount)",
        ];
    }

    public function updatedStatus($value)
    {
        $this->dispatch('ratingFilterUpdated', status: $value);
    }

    public function render()
    {
        return view('livewire.search-filters.rating-filter');
    }
}
