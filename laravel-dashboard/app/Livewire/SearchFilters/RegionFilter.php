<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegionFilter extends Component
{
    public string $selectedRegion = '';
    public array $regionOptions = [];
    public array $regionDetails = [];
    private string $lastDispatchedValue = '';

    public function mount($selectedRegion = '')
    {
        Log::info('RegionFilter: Component mounting', [
            'selectedRegion' => $selectedRegion
        ]);
        
        $this->selectedRegion = $selectedRegion;
        $this->lastDispatchedValue = $selectedRegion; // Initialize tracking
        $this->loadRegionalData();
        
        Log::info('RegionFilter: Mount completed', [
            'selectedRegion' => $this->selectedRegion,
            'regionOptions_count' => count($this->regionOptions)
        ]);
    }

    private function loadRegionalData()
    {
        $regionalData = [
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "København & Frederiksberg",
                "zip_ranges" => [[1000, 2470]],
                "municipalities" => ["København", "Frederiksberg"]
            ],
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "Vestegnen",
                "zip_ranges" => [[2600, 2690]],
                "municipalities" => ["Glostrup", "Brøndby", "Rødovre", "Albertslund", "Vallensbæk", "Taastrup", "Ishøj", "Hedehusene", "Hvidovre", "Greve", "Solrød"]
            ],
            [
                "macro_region" => "Region Hovedstaden",
                "scope" => "Nordsjælland",
                "zip_ranges" => [[2800, 2990], [3000, 3699]],
                "municipalities" => ["Lyngby-Taarbæk", "Gentofte", "Rudersdal", "Hørsholm", "Fredensborg", "Helsingør", "Gribskov", "Hillerød", "Allerød", "Frederikssund", "Egedal", "Furesø", "Halsnæs"]
            ],
            [
                "macro_region" => "Region Hovedstagen",
                "scope" => "Bornholm",
                "zip_ranges" => [[3700, 3790]],
                "municipalities" => ["Bornholm"]
            ],
            [
                "macro_region" => "Region Sjælland",
                "scope" => "Sjælland",
                "zip_ranges" => [[4000, 4990]]
            ],
            [
                "macro_region" => "Region Syddanmark",
                "scope" => "Fyn & Øer",
                "zip_ranges" => [[5000, 5999]]
            ],
            [
                "macro_region" => "Region Syddanmark",
                "scope" => "Syd- & Sønderjylland",
                "zip_ranges" => [[6000, 6999]]
            ],
            [
                "macro_region" => "Region Midtjylland",
                "scope" => "Midtjylland",
                "zip_ranges" => [[7000, 8999]]
            ],
            [
                "macro_region" => "Region Nordjylland",
                "scope" => "Nordjylland",
                "zip_ranges" => [[9000, 9999]]
            ]
        ];

        $this->regionOptions = ['' => 'All Regions'];

        // Store regional data for tooltips (keyed by scope)
        $this->regionDetails = [];

        // Count jobs for each region (simplified global count)
        foreach ($regionalData as $region) {
            $scope = $region['scope'];

            // Store region details for tooltips
            $this->regionDetails[$scope] = [
                'zip_ranges' => $region['zip_ranges'],
                'municipalities' => $region['municipalities'] ?? [],
                'macro_region' => $region['macro_region']
            ];

            // Count jobs in this region (global count for simplicity)
            $jobCount = $this->countJobsInRegion($region);

            // Create option with job count
            $label = $scope . ' (' . $jobCount . ' jobs)';
            $this->regionOptions[$scope] = $label;
        }
    }

    private function countJobsInRegion($region)
    {
        $zipRanges = $region['zip_ranges'];
        $municipalities = $region['municipalities'] ?? [];

        $query = DB::table('job_postings')
            ->whereNull('job_post_closed_date'); // Only count open jobs

        $query->where(function($q) use ($zipRanges, $municipalities) {
            // Filter by zip ranges
            if (!empty($zipRanges)) {
                $q->where(function($zipQuery) use ($zipRanges) {
                    foreach ($zipRanges as $range) {
                        $zipQuery->orWhereBetween('zipcode', [$range[0], $range[1]]);
                    }
                });
            }

            // Also filter by municipalities if available
            if (!empty($municipalities)) {
                $q->orWhereIn('city', $municipalities);
            }
        });

        return $query->count();
    }

    public function updatedSelectedRegion($value)
    {
        Log::info('RegionFilter: updatedSelectedRegion called', [
            'old_value' => $this->selectedRegion,
            'new_value' => $value,
            'lastDispatchedValue' => $this->lastDispatchedValue,
            'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        // Only dispatch if the value actually changed from what we last dispatched
        if ($value !== $this->lastDispatchedValue) {
            $this->lastDispatchedValue = $value;
            $this->dispatch('regionFilterUpdated', region: $value);
            
            Log::info('RegionFilter: regionFilterUpdated event dispatched', [
                'region' => $value
            ]);
        } else {
            Log::info('RegionFilter: Skipping dispatch - value unchanged', [
                'value' => $value
            ]);
        }
    }

    public function updatingSelectedRegion($value)
    {
        Log::info('RegionFilter: updatingSelectedRegion called (before update)', [
            'current_value' => $this->selectedRegion,
            'incoming_value' => $value,
            'lastDispatchedValue' => $this->lastDispatchedValue
        ]);
    }

    public function getRegionTooltip($regionScope)
    {
        if (!isset($this->regionDetails[$regionScope])) {
            return '';
        }

        $details = $this->regionDetails[$regionScope];
        $tooltip = $details['macro_region'] . "\n\n";

        // Add zip ranges
        if (!empty($details['zip_ranges'])) {
            $tooltip .= "Zip Ranges:\n";
            foreach ($details['zip_ranges'] as $range) {
                $tooltip .= "• " . $range[0] . " - " . $range[1] . "\n";
            }
            $tooltip .= "\n";
        }

        // Add municipalities
        if (!empty($details['municipalities'])) {
            $tooltip .= "Municipalities:\n";
            $tooltip .= "• " . implode(", ", $details['municipalities']);
        }

        return trim($tooltip);
    }

    public function render()
    {
        Log::info('RegionFilter: render called');
        return view('livewire.search-filters.region-filter');
    }
}
