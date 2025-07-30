<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class JobStatusFilter extends Component
{
    public string $status = 'open';
    public array $options = [];

    public function mount($status = 'open')
    {
        $this->status = $status;
        $this->loadOptions();
    }

    private function loadOptions()
    {
        $openCount = DB::table('job_postings')->whereNull('job_post_closed_date')->count();
        $closedCount = DB::table('job_postings')->whereNotNull('job_post_closed_date')->count();
        $total = $openCount + $closedCount;

        $this->options = [
            'open' => "Open Jobs ($openCount)",
            'closed' => "Closed Jobs ($closedCount)",
            'both' => "All Jobs ($total)",
        ];
    }

    public function updatedStatus($value)
    {
        $this->dispatch('jobStatusFilterUpdated', status: $value);
    }

    public function render()
    {
        return view('livewire.search-filters.job-status-filter');
    }
}
