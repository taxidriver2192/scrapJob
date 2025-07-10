<div class="flex items-center space-x-2 w-full {{ $containerClass }} {{ $isSelected ? $config['highlightColor'] . ' p-2 rounded-lg border-l-4 ' . $config['borderColor'] : '' }}">
    @if($showIcon)
        <i class="{{ $config['icon'] }} {{ $score ? $config['iconColor'] : 'text-zinc-400' }} {{ $isSelected ? 'text-lg' : '' }}"></i>
    @endif

    @if($showLabel)
        <span class="text-xs {{ $score ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-500 dark:text-zinc-400' }} w-16 {{ $isSelected ? 'font-semibold' : '' }}">
            {{ $config['label'] }}
        </span>
    @endif

    @if($score)
        <flux:badge
            color="{{ $badgeColor }}"
            size="{{ $badgeSize }}"
            inset="top bottom"
        >
            {{ $score }}%
        </flux:badge>
    @else
        <span class="text-xs {{ $score ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-500 dark:text-zinc-400' }}">
            N/A
        </span>
    @endif
</div>
