<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Current Role & Seniority') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('What level of role are you targeting?') }}
        </p>
    </header>

    <form wire:submit="updateProfessionalInfo" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:input
                    wire:model="current_job_title"
                    label="Current Job Title"
                    placeholder="e.g., Senior PHP Developer"
                    :invalid="$errors->has('current_job_title')"
                />
                @error('current_job_title') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="current_company"
                    label="Current Company"
                    :invalid="$errors->has('current_company')"
                />
                @error('current_company') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:select
                    wire:model="preferred_job_type"
                    label="Preferred Seniority Level"
                    placeholder="Select seniority level"
                    :invalid="$errors->has('preferred_job_type')"
                >
                    <flux:select.option value="intern">Intern/Graduate</flux:select.option>
                    <flux:select.option value="junior">Junior Developer</flux:select.option>
                    <flux:select.option value="mid">Mid-Level Developer</flux:select.option>
                    <flux:select.option value="senior">Senior Developer</flux:select.option>
                    <flux:select.option value="lead">Lead Developer</flux:select.option>
                    <flux:select.option value="principal">Principal Engineer</flux:select.option>
                    <flux:select.option value="director">Director+</flux:select.option>
                </flux:select>
                @error('preferred_job_type') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="industry"
                    label="Industry"
                    placeholder="e.g., Technology, Finance, Healthcare"
                    :invalid="$errors->has('industry')"
                />
                @error('industry') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        <!-- Management Preference -->
        <div class="mt-6">
            <flux:checkbox
                wire:model="open_to_management"
                label="Open to management roles?"
                description="Check if you're interested in people management positions"
            />
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
