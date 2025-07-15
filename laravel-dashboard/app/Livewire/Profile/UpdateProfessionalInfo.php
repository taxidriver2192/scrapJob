<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateProfessionalInfo extends Component
{
    public string $current_job_title = '';
    public string $current_company = '';
    public string $preferred_job_type = '';
    public string $industry = '';
    public bool $open_to_management = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->current_job_title = $user->current_job_title ?? '';
        $this->current_company = $user->current_company ?? '';
        $this->preferred_job_type = $user->preferred_job_type ?? '';
        $this->industry = $user->industry ?? '';
        $this->open_to_management = $user->open_to_management ?? false;
    }

    public function rules()
    {
        return [
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'preferred_job_type' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'open_to_management' => 'boolean',
        ];
    }

    /**
     * Update the professional information for the currently authenticated user.
     */
    public function updateProfessionalInfo(): void
    {
        $validated = $this->validate();

        $user = Auth::user();
        $user->update($validated);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-professional-info');
    }
}
