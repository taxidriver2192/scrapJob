<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoritesFilter extends Component
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
            
            $favoritedCount = DB::table('job_postings')
                ->join('user_job_favorites', 'job_postings.job_id', '=', 'user_job_favorites.job_id')
                ->where('user_job_favorites.user_id', $userId)
                ->whereNull('job_postings.job_post_closed_date')
                ->count();
            
            $notFavoritedCount = $totalJobs - $favoritedCount;

            $this->options = [
                '' => "All Jobs ($totalJobs)",
                'favorited' => "Favorited ($favoritedCount)",
                'not_favorited' => "Not Favorited ($notFavoritedCount)",
            ];
        } else {
            $totalJobs = DB::table('job_postings')->whereNull('job_post_closed_date')->count();
            $this->options = [
                '' => "All Jobs ($totalJobs)",
                'favorited' => "Favorited (0)",
                'not_favorited' => "Not Favorited (0)",
            ];
        }
    }

    public function updatedStatus($value)
    {
        $this->dispatch('favoritesFilterUpdated', status: $value);
    }

    public function render()
    {
        return view('livewire.search-filters.favorites-filter');
    }
}
