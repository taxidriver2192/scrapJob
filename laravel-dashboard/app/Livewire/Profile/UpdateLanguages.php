<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateLanguages extends Component
{
    public array $languages = [];
    public string $newLanguage = '';
    public string $newLanguageProficiency = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->languages = $user->languages ?? [];
    }

    public function addLanguage()
    {
        if (!empty($this->newLanguage) && !empty($this->newLanguageProficiency)) {
            $this->languages[] = [
                'language' => trim($this->newLanguage),
                'proficiency' => trim($this->newLanguageProficiency)
            ];
            $this->newLanguage = '';
            $this->newLanguageProficiency = '';
        }
    }

    public function removeLanguage($index)
    {
        unset($this->languages[$index]);
        $this->languages = array_values($this->languages);
    }

    /**
     * Update the languages for the currently authenticated user.
     */
    public function updateLanguages(): void
    {
        $user = Auth::user();
        $user->update(['languages' => $this->languages]);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-languages');
    }
}
