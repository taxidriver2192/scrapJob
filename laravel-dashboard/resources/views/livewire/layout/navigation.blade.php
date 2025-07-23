<flux:header container
             class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:brand href="{{ route('dashboard') }}" name="ScrapJob Dashboard"
                class="max-lg:hidden" wire:navigate />

    <flux:navbar class="-mb-px max-lg:hidden">
        <flux:navbar.item icon="chart-bar" href="{{ route('dashboard') }}"
                          :current="request()->routeIs('dashboard')" wire:navigate>
            Dashboard
        </flux:navbar.item>
        <flux:navbar.item icon="briefcase" href="{{ route('jobs') }}"
                          :current="request()->routeIs('jobs')" wire:navigate>
            Jobs
        </flux:navbar.item>
        <flux:navbar.item icon="building-office" href="{{ route('companies') }}"
                          :current="request()->routeIs('companies')" wire:navigate>
            Companies
        </flux:navbar.item>
        <flux:navbar.item icon="queue-list" href="{{ route('job-queue.index') }}"
                          :current="request()->routeIs('job-queue.*')" wire:navigate>
            Rating Queue
        </flux:navbar.item>
        <flux:navbar.item icon="sparkles" href="{{ route('job-ratings.index') }}"
                          :current="request()->routeIs('job-ratings.*')" wire:navigate>
            AI Ratings
        </flux:navbar.item>
    </flux:navbar>

    <flux:spacer />

    <flux:navbar class="me-4">
        <flux:navbar.item x-data x-on:click="$flux.dark = ! $flux.dark"
                          icon="moon" label="Toggle dark mode" />
    </flux:navbar>

    <flux:dropdown position="top" align="start">
        <flux:profile name="{{ auth()->user()->name }}" />

        <flux:menu>
            <flux:menu.item icon="user" href="{{ route('profile') }}" wire:navigate>
                Profile
            </flux:menu.item>
            <flux:menu.item icon="cog-6-tooth" href="{{ route('profile.edit') }}" wire:navigate>
                Edit Profile
            </flux:menu.item>

            <flux:menu.separator />

            <flux:menu.item icon="arrow-right-start-on-rectangle" wire:click="logout">
                Logout
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:header>
