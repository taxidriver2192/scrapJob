<div>
    <div class="flex items-center gap-1 mb-1">
        <flux:label>Favorites Status</flux:label>
        <flux:tooltip content="Filter by jobs you have favorited or not favorited" position="top">
            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
        </flux:tooltip>
    </div>

    <flux:select wire:model.live="status" placeholder="Select...">
        @foreach($options as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>
</div>
