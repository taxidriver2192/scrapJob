<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Languages') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('What languages do you speak?') }}
        </p>
    </header>

    <form wire:submit="updateLanguages" class="mt-6 space-y-6">
        <!-- Languages Display -->
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach($languages as $index => $language)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    {{ $language['language'] }} ({{ $language['proficiency'] }})
                    <button type="button" wire:click="removeLanguage({{ $index }})"
                            class="ml-2 text-purple-600 hover:text-purple-800 dark:text-purple-300 dark:hover:text-purple-100">
                        ×
                    </button>
                </span>
            @endforeach
        </div>

        <!-- Add New Language -->
        <div class="grid grid-cols-2 gap-2">
            <flux:input wire:model="newLanguage" placeholder="Language (e.g., Danish)" />
            <div class="flex gap-2">
                <flux:select
                    wire:model="newLanguageProficiency"
                    placeholder="Proficiency"
                    class="flex-1"
                >
                    <flux:select.option value="Basic">Basic</flux:select.option>
                    <flux:select.option value="Conversational">Conversational</flux:select.option>
                    <flux:select.option value="Fluent">Fluent</flux:select.option>
                    <flux:select.option value="Native">Native</flux:select.option>
                </flux:select>
                <flux:button type="button" wire:click="addLanguage" variant="filled" color="purple">
                    Add
                </flux:button>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
