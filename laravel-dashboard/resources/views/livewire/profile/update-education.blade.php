<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Education & Certifications') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Your educational background and professional certifications') }}
        </p>
    </header>

    <form wire:submit="updateEducation" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:select
                    wire:model="highest_education"
                    label="Highest Education Level"
                    placeholder="Select education level"
                    :invalid="$errors->has('highest_education')"
                >
                    <flux:select.option value="high_school">High School</flux:select.option>
                    <flux:select.option value="associate">Associate Degree</flux:select.option>
                    <flux:select.option value="bachelor">Bachelor's Degree</flux:select.option>
                    <flux:select.option value="master">Master's Degree</flux:select.option>
                    <flux:select.option value="phd">PhD</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
                @error('highest_education') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="field_of_study"
                    label="Field of Study"
                    placeholder="e.g., Computer Science, Engineering"
                    :invalid="$errors->has('field_of_study')"
                />
                @error('field_of_study') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="university"
                    label="University / Institution"
                    placeholder="e.g., University of Copenhagen"
                    :invalid="$errors->has('university')"
                />
                @error('university') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="graduation_year"
                    type="number"
                    label="Graduation Year"
                    min="1950"
                    max="{{ date('Y') + 10 }}"
                    placeholder="e.g., 2020"
                    :invalid="$errors->has('graduation_year')"
                />
                @error('graduation_year') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        <!-- Certifications -->
        <div>
            <flux:field>
                <flux:label>Certifications</flux:label>
                
                <!-- Certifications Display -->
                <div class="flex flex-wrap gap-2 mt-2 mb-3">
                    @foreach($certifications as $index => $certification)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ $certification }}
                            <button type="button" wire:click="removeCertification({{ $index }})"
                                    class="ml-2 text-green-600 hover:text-green-800 dark:text-green-300 dark:hover:text-green-100">
                                ×
                            </button>
                        </span>
                    @endforeach
                </div>

                <!-- Add New Certification -->
                <div class="flex gap-2">
                    <flux:input
                        wire:model="newCertification"
                        placeholder="Add a certification (e.g., AWS Certified Solutions Architect)"
                        class="flex-1"
                    />
                    <flux:button type="button" wire:click="addCertification" variant="filled" color="green">
                        Add
                    </flux:button>
                </div>
            </flux:field>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
