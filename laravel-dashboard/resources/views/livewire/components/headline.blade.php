<div class="mb-6">
    <flux:heading size="{{ $size }}" class="{{ $titleColor }}">
        @if($icon)
            <flux:icon.{{ $icon }} class="mr-2" />
        @endif
        {{ $title }}
    </flux:heading>

    @if($subtitle)
        <flux:text class="{{ $subtitleColor }}">
            {{ $subtitle }}
        </flux:text>
    @endif
</div>
