<div>
\    <flux:main container class="max-w-7xl mx-auto px-4 bg-white dark:bg-zinc-900">
        <!-- Company Shared Content -->
        <livewire:companies.shared-company-content
            :companyId="$companyId"
            :company="$company"
            :showBackButton="true"
            :key="'company-details-'.$companyId"
        />
    </flux:main>
</div>
