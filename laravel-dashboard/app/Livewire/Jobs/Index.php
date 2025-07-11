<?php

namespace App\Livewire\Jobs;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobPosting;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            // Toggle direction if same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new field with default direction
            $this->sortField = $field;
            $this->sortDirection = $field === 'created_at' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function render()
    {
        $jobs = JobPosting::with(['company', 'jobRatings' => function ($query) {
                $query->where('rating_type', 'ai_match');
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhereHas('company', function ($company) {
                          $company->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhere('location', 'like', '%' . $this->search . '%');
                });
            });

        // Apply sorting
        switch ($this->sortField) {
            case 'company':
                $jobs->join('companies', 'job_postings.company_id', '=', 'companies.company_id')
                     ->orderBy('companies.name', $this->sortDirection)
                     ->select('job_postings.*');
                break;
            case 'title':
            case 'location':
            case 'posted_date':
            case 'created_at':
                $jobs->orderBy($this->sortField, $this->sortDirection);
                break;
            default:
                $jobs->orderBy('created_at', 'desc');
                break;
        }

        $jobs = $jobs->paginate($this->perPage);

        return view('livewire.jobs.index', compact('jobs'));
    }
}
