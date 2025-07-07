<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobRating;

class Ratings extends Component
{
    use WithPagination;

    public $search = '';
    public $ratingTypeFilter = '';
    public $perPage = 20;

    protected $queryString = ['search', 'ratingTypeFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRatingTypeFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $ratings = JobRating::with(['jobPosting.company'])
            ->when($this->search, function ($query) {
                $query->whereHas('jobPosting', function ($jobQuery) {
                    $jobQuery->where('title', 'like', '%' . $this->search . '%')
                        ->orWhereHas('company', function ($companyQuery) {
                            $companyQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->ratingTypeFilter, function ($query) {
                $query->where('rating_type', $this->ratingTypeFilter);
            })
            ->orderBy('rated_at', 'desc')
            ->paginate($this->perPage);

        $ratingTypes = JobRating::distinct('rating_type')->pluck('rating_type');

        return view('livewire.ratings', compact('ratings', 'ratingTypes'));
    }
}
