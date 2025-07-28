<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\LocationService;
use App\Models\User;

class UpdateBasicInformation extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $preferredLocation = '';
    public string $yearsOfExperience = '';
    public string $dateOfBirth = '';
    public string $bio = '';
    public array $citySuggestions = [];

    protected LocationService $locationService;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->preferredLocation = $user->preferred_location ?? '';
        $this->yearsOfExperience = $user->years_of_experience ?? '';
        $this->dateOfBirth = $user->date_of_birth?->format('Y-m-d') ?? '';
        $this->bio = $user->bio ?? '';
    }

    public function boot(LocationService $locationService): void
    {
        $this->locationService = $locationService;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'preferredLocation' => 'nullable|string|max:255',
            'yearsOfExperience' => 'nullable|integer|min:0',
            'dateOfBirth' => 'nullable|date',
            'bio' => 'nullable|string',
        ];
    }

    /**
     * Update city suggestions when preferred location changes
     */
    public function updatedPreferredLocation(): void
    {
        if (strlen($this->preferredLocation) >= 2) {
            $this->citySuggestions = $this->locationService->getSuggestions($this->preferredLocation);
        } else {
            $this->citySuggestions = [];
        }
    }

    /**
     * Select a city from suggestions
     */
    public function selectCity(string $city): void
    {
        $this->preferredLocation = $city;
        $this->citySuggestions = [];
    }

    /**
     * Update the basic information for the currently authenticated user.
     */
    public function updateBasicInformation(): void
    {
        $validated = $this->validate();

        // Map property names to database column names and handle null values
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => !empty($validated['phone']) ? $validated['phone'] : null,
            'preferred_location' => !empty($validated['preferredLocation']) ? $validated['preferredLocation'] : null,
            'years_of_experience' => !empty($validated['yearsOfExperience']) ? (int)$validated['yearsOfExperience'] : null,
            'date_of_birth' => !empty($validated['dateOfBirth']) ? $validated['dateOfBirth'] : null,
            'bio' => !empty($validated['bio']) ? $validated['bio'] : null,
        ];

        /** @var User $user */
        $user = Auth::user();
        $user->update($userData);

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        return view('livewire.profile.update-basic-information');
    }
}
