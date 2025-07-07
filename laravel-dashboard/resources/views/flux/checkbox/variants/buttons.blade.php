@props([
    'size' => 'base',
    'label' => null,
    'icon' => null,
])

@php
$classes = Flux::classes()
    ->add('flex relative items-center font-medium justify-center gap-2 whitespace-nowrap')
    ->add('disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none')
    ->add(match ($size) {
        'base' => 'h-10 text-sm rounded-lg px-4 [&:has(>:not(span):first-child)]:ps-3 [&:has(>:not(span):last-child)]:pe-3',
        'sm' => 'h-8 text-sm rounded-md px-3',
        'xs' => 'h-6 text-xs rounded-md px-2',
    })
    ->add('bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75')
    ->add('text-zinc-800 dark:text-white')
    ->add('border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-500')
    ->add(match ($size) {
        'base' => 'shadow-xs',
        'sm' => 'shadow-xs',
        'xs' => 'shadow-none',
    })
    ->add('data-checked:border-zinc-800 dark:data-checked:border-white')
    ;

$iconAttributes = Flux::attributesAfter('icon:', $attributes, [
    'class' => 'text-zinc-300 dark:text-zinc-400 in-data-checked:text-zinc-800 dark:in-data-checked:text-white',
    'variant' => 'micro',
]);
@endphp

{{-- We have to put tabindex="-1" here because otherwise, Livewire requests will wipe out tabindex state, --}}
{{-- even with durable attributes for some reason... --}}
{{-- We have to put "data-flux-field" so that a single box can be disabled without "disabling" the group field label... --}}
<ui-checkbox {{ $attributes->class($classes) }} data-flux-control data-flux-checkbox-buttons tabindex="-1" data-flux-field>
    <?php if (is_string($icon) && $icon !== ''): ?>
        <flux:icon :icon="$icon" :attributes="$iconAttributes" />
    <?php elseif ($icon): ?>
        {{ $icon }}
    <?php endif; ?>

    <span>{{ $label ?? $slot }}</span>
</ui-checkbox>
