<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Carbon\Carbon;
use Flux\DateRange;
use Illuminate\Support\Facades\Log;

class DateFilter extends Component
{
    public ?DateRange $dateRange = null;

    public function mount($dateFrom = '', $dateTo = '', $datePreset = '', $dateRange = null)
    {
        // Prioritize passed DateRange object
        if ($dateRange instanceof DateRange) {
            $this->dateRange = $dateRange;
        }
        // Convert old parameters to DateRange if provided and no DateRange object exists
        elseif ($dateFrom && $dateTo) {
            $this->dateRange = new DateRange(
                Carbon::parse($dateFrom),
                Carbon::parse($dateTo)
            );
        } elseif ($datePreset) {
            $this->dateRange = $this->createDateRangeFromPreset($datePreset);
        }
    }

    public function updatedDateRange()
    {
        // Add some debugging to see if this is triggered
        Log::info('DateRange updated', [
            'dateRange' => $this->dateRange,
            'start' => $this->dateRange?->start(),
            'end' => $this->dateRange?->end(),
        ]);

        $this->emitDateUpdate();
    }

    private function createDateRangeFromPreset($preset)
    {
        return match ($preset) {
            'last_24_hours' => new DateRange(now()->subDay(), now()),
            'last_week' => DateRange::last7Days(),
            'last_month' => DateRange::lastMonth(),
            'last_3_months' => DateRange::last3Months(),
            default => null,
        };
    }

    private function emitDateUpdate()
    {
        $from = $this->dateRange?->start()?->format('Y-m-d') ?? '';
        $to = $this->dateRange?->end()?->format('Y-m-d') ?? '';

        // Try to detect preset based on date range
        $preset = '';
        if ($this->dateRange) {
            $preset = $this->detectPresetFromDateRange($this->dateRange);
        }

        $this->dispatch('dateFilterUpdated',
            from: $from,
            to: $to,
            preset: $preset
        );
    }

    private function detectPresetFromDateRange(DateRange $dateRange): string
    {
        $start = $dateRange->start();
        $end = $dateRange->end();

        if (!$start || !$end) {
            return '';
        }

        // Check for common presets by comparing with known ranges
        $presets = [
            'today' => DateRange::today(),
            'yesterday' => DateRange::yesterday(),
            'last7Days' => DateRange::last7Days(),
            'lastWeek' => DateRange::lastWeek(),
            'lastMonth' => DateRange::lastMonth(),
            'last3Months' => DateRange::last3Months(),
        ];

        foreach ($presets as $presetName => $presetRange) {
            if ($start->isSameDay($presetRange->start()) && $end->isSameDay($presetRange->end())) {
                return $presetName;
            }
        }

        return '';
    }

    public function render()
    {
        return view('livewire.search-filters.date-filter');
    }
}
