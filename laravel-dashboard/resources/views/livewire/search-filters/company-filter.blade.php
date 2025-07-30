<div>
    <div class="flex items-center gap-1 mb-1">
        <flux:label>Company</flux:label>
        <flux:tooltip content="Filter jobs by specific companies" position="top">
            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
        </flux:tooltip>
    </div>

    <flux:select wire:model.live="selectedCompany" placeholder="Select company...">
        @foreach($companyOptions as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
