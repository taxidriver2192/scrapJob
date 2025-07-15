<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Skills & Expertise') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Your top technologies & other proficiencies. Rate each skill (1-10) based on how much you enjoy working with it.') }}
        </p>
    </header>

    <form wire:submit="updateSkills" class="mt-6 space-y-6">
        <!-- Skills Display with Ratings -->
        <div class="space-y-3">
            @foreach($skills as $index => $skill)
                <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $skill['name'] }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="text-xs text-gray-600 dark:text-gray-400 min-w-0">
                            Rating:
                        </label>
                        <input type="range"
                               wire:change="updateSkillRating({{ $index }}, $event.target.value)"
                               min="1" max="10"
                               value="{{ $skill['rating'] ?? 5 }}"
                               class="w-20 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-600">
                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400 min-w-0">
                            {{ $skill['rating'] ?? 5 }}
                        </span>
                    </div>

                    <button type="button" wire:click="removeSkill({{ $index }})"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 p-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>

        <!-- Add New Skill -->
        <div class="flex gap-2">
            <flux:input wire:model="newSkill" placeholder="Add a skill (e.g., PHP, Laravel, AWS)" class="flex-1" />
            <flux:button type="button" wire:click="addSkill" variant="primary">
                Add
            </flux:button>
        </div>

        <div class="text-xs text-gray-500">
            Rating scale: 1 = Dislike, 5 = Neutral, 10 = Love working with this technology
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
