<?php

namespace App\Livewire\Components;

use Livewire\Component;

class Breadcrumbs extends Component
{
    public array $items = [];
    public bool $showHome = true;
    public string $homeUrl = '/';
    public string $homeIcon = 'home';

    public function mount(array $items = [], bool $showHome = true, string $homeUrl = '/', string $homeIcon = 'home')
    {
        $this->items = $items;
        $this->showHome = $showHome;
        $this->homeUrl = $homeUrl;
        $this->homeIcon = $homeIcon;
    }

    public function render()
    {
        return view('livewire.components.breadcrumbs');
    }
}
