<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Company;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Companies extends Component
{
    public function render()
    {
        // Get unique locations for the filters
        $locations = Company::whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values()
            ->toArray();

        // Define table configuration similar to JobTable
        $tableConfig = [
            'title' => 'Company Directory',
            'linkToDetailsPage' => true,
            'showActions' => false,
            'columns' => [
                'name' => [
                    'label' => 'Company Name',
                    'enabled' => true,
                    'type' => 'regular'
                ],
                'vat' => [
                    'label' => 'VAT Number',
                    'enabled' => true,
                    'type' => 'regular'
                ],
                'city' => [
                    'label' => 'Location',
                    'enabled' => true,
                    'type' => 'regular'
                ],
                'employees' => [
                    'label' => 'Employees',
                    'enabled' => true,
                    'type' => 'regular'
                ],
                'status' => [
                    'label' => 'Status',
                    'enabled' => true,
                    'type' => 'regular'
                ],
                'job_count' => [
                    'label' => 'Job Postings',
                    'enabled' => true,
                    'type' => 'regular'
                ]
            ]
        ];

        // Filter options for the company filters
        $filterOptions = [
            'title' => 'Company Search & Filters',
            'showStatusFilter' => true,
            'showVatFilter' => true,
            'showJobsFilter' => true,
            'showEmployeesFilter' => true,
            'showPerPage' => true,
        ];

        return view('livewire.companies', [
            'locations' => $locations,
            'tableConfig' => $tableConfig,
            'filterOptions' => $filterOptions,
        ]);
    }
}
