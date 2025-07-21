<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Work Arrangement') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Where would you like to work?') }}
        </p>
    </header>

    <form wire:submit="updateWorkArrangement" class="mt-6 space-y-6">
        <div class="space-y-4">
            <flux:checkbox
                wire:model="remote_work_preference"
                label="Hybrid / Remote Work"
            />

            <flux:checkbox
                wire:model="willing_to_relocate"
                label="Willing to relocate"
            />

            <div>
                <flux:field>
                    <flux:label>Maximum Travel Distance to Work (km)</flux:label>
                    <flux:description>How far are you willing to travel daily for work? This helps match jobs within your commute range.</flux:description>
                    <flux:input
                        wire:model="maxTravelDistance"
                        type="number"
                        placeholder="e.g., 50"
                        min="0"
                        max="500"
                        :invalid="$errors->has('maxTravelDistance')"
                    />
                    @error('maxTravelDistance') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <flux:field>
                        <flux:label>Minimum Salary Expectation</flux:label>
                        <div class="flex rounded-md shadow-sm">
                            <flux:input
                                wire:model="salary_expectation_min"
                                type="number"
                                class="flex-1 rounded-r-none border-r-0"
                                :invalid="$errors->has('salary_expectation_min')"
                            />
                            <flux:select
                                wire:model="currency"
                                class="rounded-l-none border-l-0 min-w-0"
                                style="width: 100px;"
                            >
                                <flux:select.option value="USD">USD</flux:select.option>
                                <flux:select.option value="EUR">EUR</flux:select.option>
                                <flux:select.option value="DKK">DKK</flux:select.option>
                                <flux:select.option value="GBP">GBP</flux:select.option>
                            </flux:select>
                        </div>
                        @error('salary_expectation_min') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:input
                        wire:model="salary_expectation_max"
                        type="number"
                        label="Maximum Salary Expectation"
                        :invalid="$errors->has('salary_expectation_max')"
                    />
                    @error('salary_expectation_max') <flux:error>{{ $message }}</flux:error> @enderror
                </div>
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
