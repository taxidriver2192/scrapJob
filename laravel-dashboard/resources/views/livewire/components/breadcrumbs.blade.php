<div>
    @if($showHome || count($items) > 0)
    <flux:breadcrumbs class="mb-6">
        @if($showHome)
        <flux:breadcrumbs.item href="{{ $homeUrl }}" icon="{{ $homeIcon }}" />
        @endif

        @foreach($items as $index => $item)
            @if($loop->last)
                {{-- Last item is not clickable --}}
                <flux:breadcrumbs.item>
                    @if(isset($item['icon']))
                        <flux:icon.{{ $item['icon'] }} class="mr-1" />
                    @endif
                    {{ $item['label'] }}
                </flux:breadcrumbs.item>
            @else
                {{-- Clickable breadcrumb items --}}
                <flux:breadcrumbs.item href="{{ $item['url'] }}">
                    @if(isset($item['icon']))
                        <flux:icon.{{ $item['icon'] }} class="mr-1" />
                    @endif
                    {{ $item['label'] }}
                </flux:breadcrumbs.item>
            @endif
        @endforeach
    </flux:breadcrumbs>
    @endif
</div>
