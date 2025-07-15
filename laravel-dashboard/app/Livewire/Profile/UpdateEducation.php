<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateEducation extends Component
{
    public string $highest_education = '';
    public string $field_of_study = '';
    public string $university = '';
    public string $graduation_year = '';
    public array $certifications = [];
    public string $newCertification = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->highest_education = $user->highest_education ?? '';
        $this->field_of_study = $user->field_of_study ?? '';
        $this->university = $user->university ?? '';
        $this->graduation_year = $user->graduation_year ?? '';
        $this->certifications = $user->certifications ?? [];
    }

    public function rules()
    {
        return [
            'highest_education' => 'nullable|string',
            'field_of_study' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|integer|min:1950|max:' . (date('Y') + 10),
        ];
    }

    public function addCertification()
    {
        if (!empty($this->newCertification)) {
            $this->certifications[] = trim($this->newCertification);
            $this->newCertification = '';
        }
    }

    public function removeCertification($index)
    {
        unset($this->certifications[$index]);
        $this->certifications = array_values($this->certifications);
    }

    /**
     * Update the education information for the currently authenticated user.
     */
    public function updateEducation(): void
    {
        $validated = $this->validate();
        $validated['certifications'] = $this->certifications;

        $user = Auth::user();
        $user->update($validated);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-education');
    }
}
