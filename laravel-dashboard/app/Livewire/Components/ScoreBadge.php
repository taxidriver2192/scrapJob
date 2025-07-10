<?php

namespace App\Livewire\Components;

use Livewire\Component;

class ScoreBadge extends Component
{
    public $scoreType;
    public $score;
    public $selectedMetric;
    public $size;
    public $showLabel;
    public $showIcon;
    public $containerClass;

    public function mount(
        string $scoreType,
        ?int $score = null,
        ?string $selectedMetric = null,
        string $size = 'sm',
        bool $showLabel = true,
        bool $showIcon = true,
        string $containerClass = ''
    ) {
        $this->scoreType = $scoreType;
        $this->score = $score;
        $this->selectedMetric = $selectedMetric;
        $this->size = $size;
        $this->showLabel = $showLabel;
        $this->showIcon = $showIcon;
        $this->containerClass = $containerClass;
    }

    public function render()
    {
        return view('livewire.components.score-badge', [
            'config' => $this->getScoreConfig(),
            'isSelected' => $this->isSelectedMetric(),
            'badgeColor' => $this->getBadgeColor(),
            'badgeSize' => $this->getBadgeSize(),
        ]);
    }

    protected function getScoreConfig()
    {
        $configs = [
            'overall_score' => [
                'icon' => 'fas fa-trophy',
                'iconColor' => 'text-yellow-500',
                'label' => 'Overall:',
                'highlightColor' => 'bg-yellow-50 dark:bg-yellow-900/20',
                'borderColor' => 'border-yellow-500',
            ],
            'location_score' => [
                'icon' => 'fas fa-map-marker-alt',
                'iconColor' => 'text-blue-500',
                'label' => 'Location:',
                'highlightColor' => 'bg-blue-50 dark:bg-blue-900/20',
                'borderColor' => 'border-blue-500',
            ],
            'tech_score' => [
                'icon' => 'fas fa-code',
                'iconColor' => 'text-purple-500',
                'label' => 'Tech:',
                'highlightColor' => 'bg-purple-50 dark:bg-purple-900/20',
                'borderColor' => 'border-purple-500',
            ],
            'team_size_score' => [
                'icon' => 'fas fa-users',
                'iconColor' => 'text-orange-500',
                'label' => 'Team:',
                'highlightColor' => 'bg-orange-50 dark:bg-orange-900/20',
                'borderColor' => 'border-orange-500',
            ],
            'leadership_score' => [
                'icon' => 'fas fa-crown',
                'iconColor' => 'text-indigo-500',
                'label' => 'Leader:',
                'highlightColor' => 'bg-indigo-50 dark:bg-indigo-900/20',
                'borderColor' => 'border-indigo-500',
            ],
        ];

        return $configs[$this->scoreType] ?? [
            'icon' => 'fas fa-star',
            'iconColor' => 'text-zinc-500',
            'label' => 'Score:',
            'highlightColor' => 'bg-zinc-50 dark:bg-zinc-800/50',
            'borderColor' => 'border-zinc-400',
        ];
    }

    protected function isSelectedMetric()
    {
        return $this->selectedMetric === $this->scoreType;
    }

    protected function getBadgeColor()
    {
        if (!$this->score) {
            return 'gray';
        }

        if ($this->score >= 80) {
            return 'green';
        } elseif ($this->score >= 60) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    protected function getBadgeSize()
    {
        if ($this->isSelectedMetric()) {
            return 'md';
        }

        return $this->size;
    }
}
