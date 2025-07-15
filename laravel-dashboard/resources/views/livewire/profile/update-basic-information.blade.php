<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Basic Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Where are you based and how long have you worked?') }}
        </p>
    </header>

    <form wire:submit="updateBasicInformation" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:input
                    wire:model="name"
                    label="Full Name *"
                    placeholder="Enter your full name"
                    required
                    :invalid="$errors->has('name')"
                />
                @error('name') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email Address *"
                    placeholder="Enter your email address"
                    required
                    :invalid="$errors->has('email')"
                />
                @error('email') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="phone"
                    type="tel"
                    label="Phone Number"
                    placeholder="Enter your phone number"
                    :invalid="$errors->has('phone')"
                />
                @error('phone') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <!-- Single Address Field with Autocomplete -->
            <div class="md:col-span-2">
                <flux:autocomplete
                    wire:model.live="preferredLocation"
                    label="Address"
                    placeholder="Start typing your address (e.g., Hovedgade 123, 4000 Roskilde)"
                    clearable
                    :invalid="$errors->has('preferredLocation')"
                >
                    @if(count($addressSuggestions) > 0)
                        @foreach ($addressSuggestions as $suggestion)
                            <flux:autocomplete.item
                                value="{{ $suggestion }}"
                                wire:key="address-{{ $loop->index }}"
                                wire:click="selectAddress('{{ $suggestion }}')"
                            >
                                {{ $suggestion }}
                            </flux:autocomplete.item>
                        @endforeach
                    @endif
                </flux:autocomplete>
                @error('preferredLocation') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:select
                    wire:model="yearsOfExperience"
                    label="Years of Experience"
                    placeholder="Select experience level"
                    :invalid="$errors->has('yearsOfExperience')"
                >
                    <flux:select.option value="0">0–2 years</flux:select.option>
                    <flux:select.option value="3">3–5 years</flux:select.option>
                    <flux:select.option value="6">6–9 years</flux:select.option>
                    <flux:select.option value="10">10+ years</flux:select.option>
                </flux:select>
                @error('yearsOfExperience') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="dateOfBirth"
                    type="date"
                    label="Date of Birth"
                    max="2999-12-31"
                    :invalid="$errors->has('dateOfBirth')"
                />
                @error('dateOfBirth') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        <div>
            <flux:textarea
                wire:model="bio"
                label="Bio / About You"
                placeholder="Tell us about yourself..."
                rows="3"
                :invalid="$errors->has('bio')"
            />
            @error('bio') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
