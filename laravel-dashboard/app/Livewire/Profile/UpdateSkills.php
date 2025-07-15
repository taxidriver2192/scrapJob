<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateSkills extends Component
{
    public array $skills = [];
    public string $newSkill = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->skills = $user->skills ?? [];

        // Convert old format to new format with ratings
        $this->skills = array_map(function($skill) {
            if (is_string($skill)) {
                return ['name' => $skill, 'rating' => 5];
            }
            return $skill;
        }, $this->skills);
    }

    public function addSkill()
    {
        if (!empty($this->newSkill)) {
            $this->skills[] = [
                'name' => trim($this->newSkill),
                'rating' => 5
            ];
            $this->newSkill = '';
        }
    }

    public function removeSkill($index)
    {
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function updateSkillRating($index, $rating)
    {
        if (isset($this->skills[$index])) {
            $this->skills[$index]['rating'] = (int) $rating;
        }
    }

    /**
     * Update the skills for the currently authenticated user.
     */
    public function updateSkills(): void
    {
        $user = Auth::user();
        $user->update(['skills' => $this->skills]);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-skills');
    }
}
