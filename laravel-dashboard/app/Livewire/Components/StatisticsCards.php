<?php

namespace App\Livewire\Components;

use Livewire\Component;

class StatisticsCards extends Component
{
    public $cards = [];

    public function mount($cards = [])
    {
        $this->cards = $cards;
    }

    public function render()
    {
        return view('livewire.components.statistics-cards');
    }
}
