<div>
    <flux:date-picker
        wire:model.live="dateRange"
        mode="range"
        label="Date Range"
        description="Filter jobs by posting date"
        with-presets
        presets="today yesterday last7Days lastWeek lastMonth last3Months yearToDate allTime"
        clearable
        size="sm"
        placeholder="Select date range"
    />
</div>
