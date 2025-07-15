<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UpdateWorkArrangement extends Component
{
    public bool $remote_work_preference = false;
    public bool $willing_to_relocate = false;
    public string $salary_expectation_min = '';
    public string $salary_expectation_max = '';
    public string $currency = 'USD';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->remote_work_preference = $user->remote_work_preference ?? false;
        $this->willing_to_relocate = $user->willing_to_relocate ?? false;
        $this->salary_expectation_min = $user->salary_expectation_min ?? '';
        $this->salary_expectation_max = $user->salary_expectation_max ?? '';
        $this->currency = $user->currency ?? 'USD';
    }

    public function rules()
    {
        return [
            'remote_work_preference' => 'boolean',
            'willing_to_relocate' => 'boolean',
            'salary_expectation_min' => 'nullable|integer|min:0',
            'salary_expectation_max' => 'nullable|integer|min:0',
            'currency' => 'required|string|max:3',
        ];
    }

    /**
     * Update the work arrangement for the currently authenticated user.
     */
    public function updateWorkArrangement(): void
    {
        $validated = $this->validate();

        $user = Auth::user();
        $user->update($validated);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-work-arrangement');
    }
}
