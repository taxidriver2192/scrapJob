<flux:card>
    <div class="p-4">
        <flux:heading size="md" class="mb-4 text-blue-600">
            <flux:icon.briefcase class="mr-2" />Job Information
        </flux:heading>
        <div class="space-y-3">
            <div>
                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Title</flux:subheading>
                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getJobTitle() }}</p>
            </div>
            <div>
                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Company</flux:subheading>
                @if($this->getCompanyId())
                    <a href="{{ route('company.details', ['companyId' => $this->getCompanyId()]) }}"
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors duration-200 inline-flex items-center group">
                        <flux:icon.building-office class="mr-1 size-4 text-blue-500" />
                        {{ $this->getCompanyName() }}
                        <flux:icon.arrow-top-right-on-square class="ml-1 size-3 opacity-60 group-hover:opacity-100 transition-opacity" />
                    </a>
                @else
                    <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getCompanyName() }}</p>
                @endif
            </div>
            <div>
                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Location</flux:subheading>
                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getJobLocation() }}</p>
                @if($this->getPostcode())
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->getPostcode() }}</p>
                @endif
            </div>
            <div>
                <flux:subheading class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Posted Date</flux:subheading>
                <p class="text-zinc-900 dark:text-zinc-100">{{ $this->getPostedDate() }}</p>
            </div>
        </div>
    </div>
</flux:card>
