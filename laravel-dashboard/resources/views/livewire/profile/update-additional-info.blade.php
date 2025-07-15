<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Additional Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Tell us more about your preferences') }}
        </p>
    </header>

    <form wire:submit="updateAdditionalInfo" class="mt-6 space-y-6">
        <div>
            <flux:select
                wire:model="availability"
                label="Availability"
                placeholder="Select availability"
                :invalid="$errors->has('availability')"
            >
                <flux:select.option value="immediately">Immediately</flux:select.option>
                <flux:select.option value="2weeks">2 weeks notice</flux:select.option>
                <flux:select.option value="1month">1 month notice</flux:select.option>
                <flux:select.option value="2months">2+ months</flux:select.option>
            </flux:select>
            @error('availability') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <div>
            <flux:textarea
                wire:model="additional_notes"
                label="About Your Ideal Job"
                placeholder="Looking for challenging technical roles without people management..."
                rows="3"
                description="Maximum 250 characters"
                :invalid="$errors->has('additional_notes')"
            />
            @error('additional_notes') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <!-- Preferences -->
        <div class="space-y-3">
            <flux:checkbox
                wire:model="email_notifications"
                label="Email notifications"
            />

            <flux:checkbox
                wire:model="job_alerts"
                label="Job alerts"
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
