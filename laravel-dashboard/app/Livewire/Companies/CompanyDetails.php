<?php

namespace App\Livewire\Companies;

use Livewire\Component;
use App\Models\Company;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class CompanyDetails extends Component
{
    public $companyId;
    public $company;

    public function mount($companyId)
    {
        $this->companyId = $companyId;
        $this->company = Company::find($companyId);

        if (!$this->company) {
            return redirect()->route('companies')->with('error', 'Company not found.');
        }
    }

    public function render()
    {
        return view('livewire.companies.company-details');
    }
}
