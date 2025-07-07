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

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $companies = Company::withCount('jobPostings')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('job_postings_count', 'desc')
            ->paginate($this->perPage);

        return view('livewire.companies', compact('companies'));
    }
}
