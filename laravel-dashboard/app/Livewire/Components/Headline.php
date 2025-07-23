<?php

namespace App\Livewire\Components;

use Livewire\Component;

class Headline extends Component
{
    public string $title;
    public ?string $subtitle = null;
    public ?string $icon = null;
    public string $size = 'xl';
    public string $titleColor = 'text-zinc-900 dark:text-zinc-100';
    public string $subtitleColor = 'text-zinc-600 dark:text-zinc-400';

    public function mount(
        string $title,
        ?string $subtitle = null,
        ?string $icon = null,
        string $size = 'xl',
        ?string $titleColor = null,
        ?string $subtitleColor = null
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->icon = $icon;
        $this->size = $size;
        
        if ($titleColor) {
            $this->titleColor = $titleColor;
        }
        
        if ($subtitleColor) {
            $this->subtitleColor = $subtitleColor;
        }
    }

    public function render()
    {
        return view('livewire.components.headline');
    }
}
