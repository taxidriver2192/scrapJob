<div>
    <div class="flex items-center gap-1 mb-1">
        <flux:label>Region</flux:label>
        <flux:tooltip content="Filter jobs by Danish regions" position="top">
            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
        </flux:tooltip>
    </div>

    <flux:select variant="combobox" wire:model.live="selectedRegion" placeholder="Select region...">
        @foreach($regionOptions as $value => $label)
            @if($value)
                <flux:select.option value="{{ $value }}" title="{{ $this->getRegionTooltip($value) }}">
                    {{ $label }}
                </flux:select.option>
            @else
                <flux:select.option value="{{ $value }}">
                    {{ $label }}
                </flux:select.option>
            @endif
        @endforeach
    </flux:select>
</div>
