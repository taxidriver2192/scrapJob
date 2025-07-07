<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Company;

class Companies extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 20;
    public $sortField = 'job_postings_count';
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
            $this->sortDirection = $field === 'job_postings_count' ? 'desc' : 'asc';
        }
        $this->resetPage();
    }

    public function render()
    {
        $companies = Company::withCount('jobPostings')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            });

        // Apply sorting
        switch ($this->sortField) {
            case 'name':
                $companies->orderBy('name', $this->sortDirection);
                break;
            case 'job_postings_count':
                $companies->orderBy('job_postings_count', $this->sortDirection);
                break;
            default:
                $companies->orderBy('job_postings_count', 'desc');
                break;
        }

        $companies = $companies->paginate($this->perPage);

        return view('livewire.companies', compact('companies'));
    }
}
