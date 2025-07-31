<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

class SkillsFilter extends Component
{
    public array $skillsFilter = [];
    public string $search = '';
    public string $selectedSkill = '';

    #[Computed]
    public function skillsCount()
    {
        return count($this->skillsFilter);
    }

    #[Computed]
    public function availableSkills()
    {
        // Get all skills with their job counts
        $skillsWithCounts = DB::table('job_postings')
            ->whereNotNull('skills')
            ->where('skills', '!=', '')
            ->whereNull('job_post_closed_date') // Only count active jobs
            ->pluck('skills')
            ->flatMap(function ($skillsJson) {
                $skills = json_decode($skillsJson, true);
                return is_array($skills) ? $skills : [];
            })
            ->filter()
            ->countBy() // This gives us skill => count
            ->when($this->search, function ($collection) {
                return $collection->filter(function ($_, $skill) {
                    return str_contains(strtolower($skill), strtolower($this->search));
                });
            })
            ->sortKeys()
            ->take(200) // Limit for performance
            ->mapWithKeys(function ($count, $skill) {
                return [$skill => "$skill ($count)"];
            });

        return $skillsWithCounts->toArray();
    }

    public function updatedSelectedSkill($value)
    {
        if ($value && !in_array($value, $this->skillsFilter)) {
            $this->skillsFilter[] = $value;
            // Use the simpler dispatch method
            $this->dispatch('skillsFilterUpdated', skills: $this->skillsFilter);
        }
        $this->selectedSkill = ''; // Clear the selection
        $this->search = ''; // Clear the search
    }

    public function removeSkill($skill)
    {
        $this->skillsFilter = array_values(array_filter($this->skillsFilter, fn($s) => $s !== $skill));

        // Use the simpler dispatch method
        $this->dispatch('skillsFilterUpdated', skills: $this->skillsFilter);
    }

    public function clearSkills()
    {
        $this->skillsFilter = [];
        $this->dispatch('skillsFilterUpdated', skills: $this->skillsFilter);
    }

    public function mount($skillsFilter = [])
    {
        $this->skillsFilter = is_array($skillsFilter) ? $skillsFilter : [];
    }

    public function render()
    {
        return view('livewire.search-filters.skills-filter');
    }
}
