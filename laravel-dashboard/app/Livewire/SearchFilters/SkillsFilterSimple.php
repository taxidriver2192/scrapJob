<?php

namespace App\Livewire\SearchFilters;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

class SkillsFilterSimple extends Component
{
    public array $skillsFilter = [];
    public string $search = '';
    public string $selectedSkill = '';

    #[Computed]
    public function availableSkills()
    {
        Log::info('SkillsFilter: Computing available skills', [
            'search' => $this->search,
            'skillsFilter' => $this->skillsFilter,
        ]);

        // Simple query without complex filtering for now
        $skills = DB::table('job_postings')
            ->whereNotNull('skills')
            ->where('skills', '!=', '')
            ->pluck('skills')
            ->flatMap(function ($skillsJson) {
                $skills = json_decode($skillsJson, true);
                return is_array($skills) ? $skills : [];
            })
            ->filter()
            ->unique()
            ->when($this->search, function ($collection) {
                return $collection->filter(function ($skill) {
                    return str_contains(strtolower($skill), strtolower($this->search));
                });
            })
            ->sort()
            ->take(200) // Limit for performance
            ->mapWithKeys(function ($skill) {
                return [$skill => $skill];
            });

        Log::info('SkillsFilter: Available skills computed', [
            'count' => count($skills),
            'first_5' => array_slice($skills->toArray(), 0, 5, true),
        ]);

        return $skills->toArray();
    }

    public function updatedSelectedSkill($value)
    {
        Log::info('SkillsFilter: Skill selected', ['skill' => $value]);

        if ($value && !in_array($value, $this->skillsFilter)) {
            $this->skillsFilter[] = $value;
            // Emit event to parent component when skills are updated
            $this->dispatch('skillsFilterUpdated', $this->skillsFilter);
        }
        $this->selectedSkill = ''; // Clear the selection
        $this->search = ''; // Clear the search
    }

    public function removeSkill($skill)
    {
        Log::info('SkillsFilter: Removing skill', ['skill' => $skill]);

        $this->skillsFilter = array_values(array_filter($this->skillsFilter, fn($s) => $s !== $skill));

        // Emit event to parent component
        $this->dispatch('skillsFilterUpdated', $this->skillsFilter);
    }

    public function clearSkills()
    {
        Log::info('SkillsFilter: Clearing all skills');

        $this->skillsFilter = [];
        $this->dispatch('skillsFilterUpdated', $this->skillsFilter);
    }

    public function mount($skillsFilter = [])
    {
        Log::info('SkillsFilter: Mounting component', ['skillsFilter' => $skillsFilter]);

        $this->skillsFilter = $skillsFilter;
    }

    public function render()
    {
        Log::info('SkillsFilter: Rendering component');

        return view('livewire.search-filters.skills-filter');
    }
}
