<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobPosting;

class Jobs extends Component
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
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.jobs', compact('jobs'));
    }
}
