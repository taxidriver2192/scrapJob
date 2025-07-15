<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateAdditionalInfo extends Component
{
    public string $availability = '';
    public string $additional_notes = '';
    public bool $email_notifications = true;
    public bool $job_alerts = true;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->availability = $user->availability ?? '';
        $this->additional_notes = $user->additional_notes ?? '';
        $this->email_notifications = $user->email_notifications ?? true;
        $this->job_alerts = $user->job_alerts ?? true;
    }

    public function rules()
    {
        return [
            'availability' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'email_notifications' => 'boolean',
            'job_alerts' => 'boolean',
        ];
    }

    /**
     * Update the additional information for the currently authenticated user.
     */
    public function updateAdditionalInfo(): void
    {
        $validated = $this->validate();

        $user = Auth::user();
        $user->update($validated);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-additional-info');
    }
}
