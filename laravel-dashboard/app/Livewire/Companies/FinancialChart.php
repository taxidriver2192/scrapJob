<?php

namespace App\Livewire\Companies;

use Livewire\Component;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class FinancialChart extends Component
{
    public $company;
    public $financialData = [];
    public $showChart = false;

    public function mount($company)
    {
        $this->company = $company;
        $this->processFinancialData();
    }

    private function processFinancialData()
    {
        if (!$this->company || !$this->company->financial_summary) {
            $this->showChart = false;
            return;
        }

        $rawData = $this->company->financial_summary;

        // Handle both array and JSON string formats
        if (is_string($rawData)) {
            // Check if it's just an empty array string
            if (trim($rawData, '"') === '[]' || $rawData === '[]') {
                $this->showChart = false;
                return;
            }

            $decoded = json_decode($rawData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rawData = $decoded;
            } else {
                // Try to handle it as a raw string that might need cleaning
                $cleanedData = trim($rawData, '"');

                // Check again after cleaning
                if ($cleanedData === '[]') {
                    $this->showChart = false;
                    return;
                }

                $decoded = json_decode($cleanedData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $rawData = $decoded;
                } else {
                    Log::warning("Failed to decode financial_summary JSON for company {$this->company->company_id}: " . json_last_error_msg());
                    $this->showChart = false;
                    return;
                }
            }
        }

        // Check if the decoded array is empty
        if (!is_array($rawData) || empty($rawData)) {
            $this->showChart = false;
            return;
        }

        // Transform the data for the chart
        $this->financialData = collect($rawData)
            ->sortBy('year')
            ->map(function ($item) {
                return [
                    'year' => (int)($item['year'] ?? 0),
                    'gross_profit' => (float) ($item['gross_profit'] ?? 0),
                    'net_profit' => (float) ($item['net_profit'] ?? 0),
                    'total_equity' => (float) ($item['total_equity'] ?? 0),
                    'balance_sheet_status' => (float) ($item['balance_sheet_status'] ?? 0),
                ];
            })
            ->values()
            ->toArray();

        $this->showChart = !empty($this->financialData);
    }

    public function getIndexedDataProperty()
    {
        if (empty($this->financialData)) {
            return [];
        }

        $data = collect($this->financialData);
        $baseYear = $data->first();

        if (!$baseYear) {
            return [];
        }

        // Get base values (using the first year as base = 100)
        $baseGrossProfit = max((float) $baseYear['gross_profit'], 1); // Avoid division by zero
        $baseTotalEquity = max((float) $baseYear['total_equity'], 1);
        $baseBalanceSheet = max((float) $baseYear['balance_sheet_status'], 1);

        return $data->map(function ($item) use ($baseGrossProfit, $baseTotalEquity, $baseBalanceSheet) {
            return [
                'year' => $item['year'],
                'gross_profit_index' => round(($item['gross_profit'] / $baseGrossProfit) * 100, 1),
                'total_equity_index' => round(($item['total_equity'] / $baseTotalEquity) * 100, 1),
                'balance_sheet_index' => round(($item['balance_sheet_status'] / $baseBalanceSheet) * 100, 1),
            ];
        })->toArray();
    }

    public function getBaseYearDataProperty()
    {
        if (empty($this->financialData)) {
            return null;
        }

        return collect($this->financialData)->first();
    }

    public function getTrendAnalysisProperty()
    {
        if (count($this->financialData) < 2) {
            return null;
        }

        $data = collect($this->financialData);
        $oldestData = $data->first();
        $newestData = $data->last();
        $yearSpan = $newestData['year'] - $oldestData['year'];

        if ($yearSpan <= 0) {
            return null;
        }

        // Calculate CAGR and trends
        $grossProfitCAGR = $oldestData['gross_profit'] > 0
            ? (pow($newestData['gross_profit'] / $oldestData['gross_profit'], 1 / $yearSpan) - 1) * 100
            : 0;
        $equityCAGR = $oldestData['total_equity'] > 0
            ? (pow($newestData['total_equity'] / $oldestData['total_equity'], 1 / $yearSpan) - 1) * 100
            : 0;
        $balanceCAGR = $oldestData['balance_sheet_status'] > 0
            ? (pow($newestData['balance_sheet_status'] / $oldestData['balance_sheet_status'], 1 / $yearSpan) - 1) * 100
            : 0;

        // Calculate percentage trends (total change over the period)
        $grossProfitTrend = $oldestData['gross_profit'] > 0
            ? (($newestData['gross_profit'] - $oldestData['gross_profit']) / $oldestData['gross_profit']) * 100
            : 0;
        $equityTrend = $oldestData['total_equity'] > 0
            ? (($newestData['total_equity'] - $oldestData['total_equity']) / $oldestData['total_equity']) * 100
            : 0;
        $balanceTrend = $oldestData['balance_sheet_status'] > 0
            ? (($newestData['balance_sheet_status'] - $oldestData['balance_sheet_status']) / $oldestData['balance_sheet_status']) * 100
            : 0;

        return [
            'yearSpan' => $yearSpan,
            'oldestData' => $oldestData,
            'newestData' => $newestData,
            'grossProfitCAGR' => round($grossProfitCAGR, 1),
            'equityCAGR' => round($equityCAGR, 1),
            'balanceCAGR' => round($balanceCAGR, 1),
            'grossProfitTrend' => $grossProfitTrend,
            'equityTrend' => $equityTrend,
            'balanceTrend' => $balanceTrend,
        ];
    }

    public function getYearOverYearProperty()
    {
        if (!$this->latestYearData || !$this->previousYearData) {
            return null;
        }

        $grossProfitChange = $this->latestYearData['gross_profit'] - $this->previousYearData['gross_profit'];
        $equityChange = $this->latestYearData['total_equity'] - $this->previousYearData['total_equity'];
        $balanceChange = $this->latestYearData['balance_sheet_status'] - $this->previousYearData['balance_sheet_status'];

        $grossProfitPercent = $this->previousYearData['gross_profit'] > 0 ? ($grossProfitChange / $this->previousYearData['gross_profit']) * 100 : 0;
        $equityPercent = $this->previousYearData['total_equity'] > 0 ? ($equityChange / $this->previousYearData['total_equity']) * 100 : 0;
        $balancePercent = $this->previousYearData['balance_sheet_status'] > 0 ? ($balanceChange / $this->previousYearData['balance_sheet_status']) * 100 : 0;

        return [
            'comparisonData' => [
                ['metric' => 'Gross Profit', 'change_percent' => round($grossProfitPercent, 2), 'absolute_change' => $grossProfitChange],
                ['metric' => 'Total Equity', 'change_percent' => round($equityPercent, 2), 'absolute_change' => $equityChange],
                ['metric' => 'Balance Sheet', 'change_percent' => round($balancePercent, 2), 'absolute_change' => $balanceChange],
            ],
            'grossProfitChange' => $grossProfitChange,
            'equityChange' => $equityChange,
            'balanceChange' => $balanceChange,
            'grossProfitPercent' => round($grossProfitPercent, 1),
            'equityPercent' => round($equityPercent, 1),
            'balancePercent' => round($balancePercent, 1),
            'positiveChanges' => collect([
                ['change_percent' => $grossProfitPercent],
                ['change_percent' => $equityPercent],
                ['change_percent' => $balancePercent]
            ])->where('change_percent', '>', 0)->count(),
            'totalMetrics' => 3,
        ];
    }

    public function getIndexChangesProperty()
    {
        $indexedData = $this->indexedData;
        if (empty($indexedData)) {
            return null;
        }

        $latestIndexed = collect($indexedData)->last();
        if (!$latestIndexed) {
            return null;
        }

        return [
            'grossProfitIndexChange' => round(($latestIndexed['gross_profit_index'] ?? 100) - 100, 1),
            'equityIndexChange' => round(($latestIndexed['total_equity_index'] ?? 100) - 100, 1),
            'balanceIndexChange' => round(($latestIndexed['balance_sheet_index'] ?? 100) - 100, 1),
        ];
    }

    public function getTrendSummaryProperty()
    {
        $trendAnalysis = $this->trendAnalysis;
        if (!$trendAnalysis) {
            return null;
        }

        $trends = [
            $trendAnalysis['grossProfitTrend'],
            $trendAnalysis['equityTrend'],
            $trendAnalysis['balanceTrend']
        ];

        $positiveTrends = collect($trends)->filter(fn($trend) => $trend > 0)->count();
        $avgCAGR = ($trendAnalysis['grossProfitCAGR'] + $trendAnalysis['equityCAGR'] + $trendAnalysis['balanceCAGR']) / 3;

        return [
            'positiveTrends' => $positiveTrends,
            'avgCAGR' => round($avgCAGR, 1),
            'oldestYear' => $trendAnalysis['oldestData']['year'],
            'newestYear' => $trendAnalysis['newestData']['year'],
        ];
    }

    public function getLatestYearDataProperty()
    {
        if (empty($this->financialData)) {
            return null;
        }

        return collect($this->financialData)->sortByDesc('year')->first();
    }

    public function getPreviousYearDataProperty()
    {
        if (count($this->financialData) < 2) {
            return null;
        }

        return collect($this->financialData)->sortByDesc('year')->skip(1)->first();
    }

    public function getEnhancedFinancialDataProperty()
    {
        if (empty($this->financialData)) {
            return [];
        }

        $data = collect($this->financialData);
        $result = [];

        foreach ($data as $index => $item) {
            $enhancedItem = $item;

            // Add year-over-year changes if not first item
            if ($index > 0) {
                $previousItem = $data[$index - 1];

                // Calculate changes
                $grossProfitChange = $item['gross_profit'] - $previousItem['gross_profit'];
                $totalEquityChange = $item['total_equity'] - $previousItem['total_equity'];
                $balanceSheetChange = $item['balance_sheet_status'] - $previousItem['balance_sheet_status'];

                // Calculate percentages
                $grossProfitPercent = $previousItem['gross_profit'] > 0 ? ($grossProfitChange / $previousItem['gross_profit']) * 100 : 0;
                $totalEquityPercent = $previousItem['total_equity'] > 0 ? ($totalEquityChange / $previousItem['total_equity']) * 100 : 0;
                $balanceSheetPercent = $previousItem['balance_sheet_status'] > 0 ? ($balanceSheetChange / $previousItem['balance_sheet_status']) * 100 : 0;

                // Create display strings
                $enhancedItem['gross_profit_change_display'] = $this->formatChange($grossProfitChange, $grossProfitPercent);
                $enhancedItem['total_equity_change_display'] = $this->formatChange($totalEquityChange, $totalEquityPercent);
                $enhancedItem['balance_sheet_change_display'] = $this->formatChange($balanceSheetChange, $balanceSheetPercent);
                $enhancedItem['previous_year'] = $previousItem['year'];
            } else {
                // First year has no previous year to compare
                $baseYearMessage = 'N/A (Base Year)';
                $enhancedItem['gross_profit_change_display'] = $baseYearMessage;
                $enhancedItem['total_equity_change_display'] = $baseYearMessage;
                $enhancedItem['balance_sheet_change_display'] = $baseYearMessage;
                $enhancedItem['previous_year'] = null;
            }

            $result[] = $enhancedItem;
        }

        return $result;
    }

    private function formatChange($change, $percent)
    {
        $formattedChange = number_format(abs($change / 1000), 0) . 'K';
        $formattedPercent = number_format(abs($percent), 1) . '%';

        if ($change >= 0) {
            return "↗ +{$formattedChange} (+{$formattedPercent})";
        } else {
            return "↘ -{$formattedChange} (-{$formattedPercent})";
        }
    }

    public function render()
    {
        return view('livewire.companies.financial-chart');
    }
}
