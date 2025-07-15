<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobRating;
use Flux\Flux;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Ratings extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedMetric = 'overall_score';
    public $ratingTypeFilter = '';
    public $companyFilter = '';
    public $scoreRangeFilter = '';
    public $locationFilter = '';
    public $dateFilter = '';
    public $perPage = 20;
    public $sortField = 'overall_score';
    public $sortDirection = 'desc';
    public $selectedRating = null;
    public $currentRatingIndex = 0;
    public $totalRatings = 0;
    public $currentRatings = [];

    protected $queryString = ['search', 'selectedMetric', 'ratingTypeFilter', 'companyFilter', 'scoreRangeFilter', 'locationFilter', 'sortField', 'sortDirection'];

    protected $listeners = [
        'previousRating' => 'previousRating',
        'nextRating' => 'nextRating'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter()
    {
        $this->resetPage();
    }

    public function updatingScoreRangeFilter()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function updatingSelectedMetric()
    {
        $this->resetPage();
    }

    public function updatingSortField()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

    public function filterByCompany($companyName)
    {
        $this->companyFilter = $companyName;
        $this->resetPage();
    }

    public function filterByScoreRange($range)
    {
        $this->scoreRangeFilter = $range;
        $this->resetPage();
    }

    public function filterByDate($date)
    {
        $this->dateFilter = $date;
        $this->resetPage();
    }

    public function filterByRatingType($type)
    {
        $this->ratingTypeFilter = $type;
        $this->resetPage();
    }

    public function clearFilter($filterType)
    {
        switch ($filterType) {
            case 'company':
                $this->companyFilter = '';
                break;
            case 'scoreRange':
                $this->scoreRangeFilter = '';
                break;
            case 'ratingType':
                $this->ratingTypeFilter = '';
                break;
            case 'location':
                $this->locationFilter = '';
                break;
            case 'date':
                $this->dateFilter = '';
                break;
            case 'search':
                $this->search = '';
                break;
            default:
                // No action for unknown filter types
                break;
        }
        $this->resetPage();
    }

    public function clearAllFilters()
    {
        $this->search = '';
        $this->ratingTypeFilter = '';
        $this->companyFilter = '';
        $this->scoreRangeFilter = '';
        $this->locationFilter = '';
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function viewDetails($ratingId)
    {
        $this->selectedRating = JobRating::with(['jobPosting.company'])->find($ratingId);

        // Store current ratings for navigation
        $ratingsCollection = $this->getFilteredRatings()->get();
        $this->currentRatings = $ratingsCollection->toArray();
        $this->totalRatings = $ratingsCollection->count();

        // Find current index
        $this->currentRatingIndex = $ratingsCollection->search(function ($rating) use ($ratingId) {
            return ($rating->id ?? $rating->rating_id) == $ratingId;
        });

        if ($this->selectedRating) {
            // Convert the selected rating to array format for the modal
            $ratingArray = $this->selectedRating->toArray();

            $this->dispatch('openJobModal',
                $ratingArray,
                $this->currentRatingIndex,
                $this->totalRatings
            );
        }
    }

    public function nextRating()
    {
        if ($this->currentRatingIndex < $this->totalRatings - 1) {
            $this->currentRatingIndex++;
            $nextRatingId = $this->currentRatings[$this->currentRatingIndex]['id'] ?? $this->currentRatings[$this->currentRatingIndex]['rating_id'];
            $this->selectedRating = JobRating::with(['jobPosting.company'])->find($nextRatingId);

            // Refresh the modal with new data
            $ratingArray = $this->selectedRating->toArray();
            $this->dispatch('refreshJobModal',
                $ratingArray,
                $this->currentRatingIndex,
                $this->totalRatings
            );
        }
    }

    public function previousRating()
    {
        if ($this->currentRatingIndex > 0) {
            $this->currentRatingIndex--;
            $prevRatingId = $this->currentRatings[$this->currentRatingIndex]['id'] ?? $this->currentRatings[$this->currentRatingIndex]['rating_id'];
            $this->selectedRating = JobRating::with(['jobPosting.company'])->find($prevRatingId);

            // Refresh the modal with new data
            $ratingArray = $this->selectedRating->toArray();
            $this->dispatch('refreshJobModal',
                $ratingArray,
                $this->currentRatingIndex,
                $this->totalRatings
            );
        }
    }

    private function getFilteredRatings()
    {
        return JobRating::with(['jobPosting.company'])
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
            ->when($this->companyFilter, function ($query) {
                $query->whereHas('jobPosting.company', function ($companyQuery) {
                    $companyQuery->where('name', 'like', '%' . $this->companyFilter . '%');
                });
            })
            ->when($this->scoreRangeFilter, function ($query) {
                switch ($this->scoreRangeFilter) {
                    case 'high':
                        $query->where('overall_score', '>=', 80);
                        break;
                    case 'medium':
                        $query->whereBetween('overall_score', [60, 79]);
                        break;
                    case 'low':
                        $query->where('overall_score', '<', 60);
                        break;
                    default:
                        break;
                }
            })
            ->when($this->locationFilter, function ($query) {
                $query->whereHas('jobPosting', function ($jobQuery) {
                    $jobQuery->where('location', 'like', '%' . $this->locationFilter . '%');
                });
            })
            ->when($this->dateFilter, function ($query) {
                $query->whereDate('rated_at', $this->dateFilter);
            })
            ->when($this->sortField, function ($query) {
                switch ($this->sortField) {
                    case 'company':
                        $query->join('job_postings', 'job_ratings.job_id', '=', 'job_postings.job_id')
                              ->join('companies', 'job_postings.company_id', '=', 'companies.company_id')
                              ->orderBy('companies.name', $this->sortDirection)
                              ->select('job_ratings.*');
                        break;
                    case 'overall_score':
                    case 'location_score':
                    case 'tech_score':
                    case 'team_size_score':
                    case 'leadership_score':
                        $query->orderBy($this->sortField, $this->sortDirection);
                        break;
                    case 'rated_at':
                        $query->orderBy('rated_at', $this->sortDirection);
                        break;
                    default:
                        $query->orderBy('overall_score', 'desc');
                        break;
                }
            }, function ($query) {
                // Default sorting when no sortField is set
                $query->orderBy('overall_score', 'desc');
            });
    }

    public function getRatingTypes()
    {
        return JobRating::distinct()
            ->whereNotNull('rating_type')
            ->where('rating_type', '!=', '')
            ->pluck('rating_type')
            ->sort()
            ->values();
    }

    public function render()
    {
        $ratings = $this->getFilteredRatings()->paginate($this->perPage);

        $ratingTypes = $this->getRatingTypes();

        $companies = JobRating::with('jobPosting.company')
            ->whereHas('jobPosting.company')
            ->get()
            ->pluck('jobPosting.company.name')
            ->unique()
            ->sort()
            ->values();

        $locations = JobRating::with('jobPosting')
            ->whereHas('jobPosting', function ($query) {
                $query->whereNotNull('location');
            })
            ->get()
            ->pluck('jobPosting.location')
            ->unique()
            ->sort()
            ->values();

        return view('livewire.ratings', compact('ratings', 'ratingTypes', 'companies', 'locations'));
    }
}
