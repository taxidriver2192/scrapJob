<?php

namespace App\Livewire\JobRatings;

use App\Models\JobRating;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $jobIdFilter = '';
    public $modelFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'jobIdFilter' => ['except' => ''],
        'modelFilter' => ['except' => ''],
    ];

    public function mount()
    {
        // Auto-populate jobIdFilter from query parameter
        if (request()->has('job_id')) {
            $this->jobIdFilter = request()->get('job_id');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedJobIdFilter()
    {
        $this->resetPage();
    }

    public function updatedModelFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->jobIdFilter = '';
        $this->modelFilter = '';
        $this->resetPage();
    }

    public function clearJobFilter()
    {
        $this->jobIdFilter = '';
        $this->resetPage();
    }

    public function getRatingsProperty()
    {
        $query = JobRating::where('user_id', Auth::id())
            ->where('rating_type', 'ai_rating')
            ->with(['user'])
            ->orderBy('rated_at', 'desc');

        // Apply filters
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('response', 'like', '%' . $this->search . '%')
                  ->orWhere('prompt', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->jobIdFilter) {
            $query->where('job_id', 'like', '%' . $this->jobIdFilter . '%');
        }

        if ($this->modelFilter) {
            $query->where('model', $this->modelFilter);
        }

        return $query->paginate(10);
    }

    public function getAvailableModelsProperty()
    {
        return JobRating::where('user_id', Auth::id())
            ->where('rating_type', 'ai_rating')
            ->distinct()
            ->pluck('model')
            ->filter();
    }

    public function getStatisticsProperty()
    {
        $query = JobRating::where('user_id', Auth::id())
            ->where('rating_type', 'ai_rating');

        // Apply same filters as main query for consistent stats
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('response', 'like', '%' . $this->search . '%')
                  ->orWhere('prompt', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->jobIdFilter) {
            $query->where('job_id', 'like', '%' . $this->jobIdFilter . '%');
        }

        if ($this->modelFilter) {
            $query->where('model', $this->modelFilter);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_jobs,
            SUM(cost) as total_cost_usd,
            AVG(cost) as avg_cost_usd,
            SUM(total_tokens) as total_tokens
        ')->first();

        // Convert USD to DKK (approximate rate: 1 USD = 7 DKK)
        $usdToDkk = 7.0;

        return [
            'total_jobs' => $stats->total_jobs ?? 0,
            'total_cost_usd' => $stats->total_cost_usd ?? 0,
            'total_cost_dkk' => ($stats->total_cost_usd ?? 0) * $usdToDkk,
            'avg_cost_usd' => $stats->avg_cost_usd ?? 0,
            'avg_cost_dkk' => ($stats->avg_cost_usd ?? 0) * $usdToDkk,
            'total_tokens' => $stats->total_tokens ?? 0,
        ];
    }

    public function convertToDkk($usdAmount)
    {
        $usdToDkk = 7.0; // Approximate exchange rate
        return $usdAmount * $usdToDkk;
    }

    public function render()
    {
        return view('livewire.job-ratings.index', [
            'ratings' => $this->ratings,
            'availableModels' => $this->availableModels,
            'statistics' => $this->statistics,
        ]);
    }
}
