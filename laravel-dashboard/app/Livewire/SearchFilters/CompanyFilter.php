<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyFilter extends Component
{
    public ?string $selectedCompany = '';
    public array $companyOptions = [];

    public function mount($selectedCompany = '')
    {
        $this->selectedCompany = $selectedCompany;
        $this->loadCompanyOptions();
    }

    private function loadCompanyOptions()
    {
        // Get companies with job counts (simplified, not filtered by other criteria)
        $companyCounts = DB::table('job_postings')
            ->join('companies', 'job_postings.company_id', '=', 'companies.company_id')
            ->select('companies.name', DB::raw('COUNT(*) as job_count'))
            ->whereNull('job_postings.job_post_closed_date') // Only open jobs for counts
            ->groupBy('companies.name')
            ->orderBy('companies.name')
            ->get();

        $this->companyOptions = ['' => 'All Companies'];

        foreach ($companyCounts as $company) {
            $this->companyOptions[$company->name] = $company->name . ' (' . $company->job_count . ' jobs)';
        }
    }

    public function updatedSelectedCompany($value)
    {
        $this->dispatch('companyFilterUpdated', company: $value);
    }

    public function render()
    {
        return view('livewire.search-filters.company-filter');
    }
}
