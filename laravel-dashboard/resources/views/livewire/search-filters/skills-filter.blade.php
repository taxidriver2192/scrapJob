<!-- Skills Filter Component -->
<div>
    <div class="flex items-center gap-1 mb-1">
        <flux:label>Skills</flux:label>
        <flux:tooltip content="Select multiple skills to filter jobs" position="top">
            <flux:icon.question-mark-circle class="w-4 h-4 text-zinc-400 hover:text-zinc-600 cursor-help" />
        </flux:tooltip>
    </div>

    <flux:select wire:model="selectedSkill" variant="combobox" :filter="false" placeholder="Search skills..." icon="code-bracket">
        <x-slot name="input">
            <flux:select.input wire:model.live="search" />
        </x-slot>
        @foreach ($this->availableSkills as $skillValue => $skillLabel)
            <flux:select.option value="{{ $skillValue }}" wire:key="skill-{{ $skillValue }}">
                {{ $skillLabel }}
            </flux:select.option>
        @endforeach
    </flux:select>
</div>
