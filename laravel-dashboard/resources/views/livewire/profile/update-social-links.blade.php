<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Professional Links') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Your online professional presence') }}
        </p>
    </header>

    <form wire:submit="updateSocialLinks" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:input
                    wire:model.blur="website"
                    type="url"
                    label="Website / Portfolio"
                    placeholder="https://yourwebsite.com"
                    description="Your personal website, portfolio, or blog (not social media)"
                    :invalid="$errors->has('website')"
                />
                @error('website') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model.blur="linkedin_url"
                    type="url"
                    label="LinkedIn Profile"
                    placeholder="https://linkedin.com/in/yourprofile"
                    description="Format: https://linkedin.com/in/your-username"
                    :invalid="$errors->has('linkedin_url')"
                />
                @error('linkedin_url') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model.blur="github_url"
                    type="url"
                    label="GitHub Profile"
                    placeholder="https://github.com/yourusername"
                    description="Format: https://github.com/your-username"
                    :invalid="$errors->has('github_url')"
                />
                @error('github_url') <flux:error>{{ $message }}</flux:error> @enderror
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
