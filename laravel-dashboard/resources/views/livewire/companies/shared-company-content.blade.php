@push('breadcrumbs')
    @if($company)
        <livewire:components.breadcrumbs
            :items="[
                ['label' => 'Companies', 'url' => route('companies'), 'icon' => 'building-office'],
                ['label' => $company->name]
            ]"
        />
    @endif
@endpush

<div>
    @if($company)
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                @if($showBackButton)
                <flux:button
                    size="sm"
                    wire:click="goBackToDashboard"
                    variant="outline"
                    icon="arrow-left"
                >
                    Back to Companies
                </flux:button>
                @endif
                <flux:heading size="lg">
                    <flux:icon.building-office class="mr-2 text-blue-500" />
                    Company Details
                </flux:heading>
                @if($showNavigation && $currentIndex !== null && $total !== null)
                <div class="flex items-center space-x-2">
                    <flux:button
                        size="sm"
                        wire:click="previousCompany"
                        variant="outline"
                        icon="chevron-left"
                    >
                        Previous
                    </flux:button>
                    <span class="text-sm text-zinc-500 px-2">{{ $currentIndex + 1 }} of {{ $total }}</span>
                    <flux:button
                        size="sm"
                        wire:click="nextCompany"
                        variant="outline"
                        icon="chevron-right"
                    >
                        Next
                    </flux:button>
                </div>
                @endif
            </div>
        </div>

        <!-- Company Information Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Basic Company Information -->
            <flux:card>
                <div class="p-4">
                    <flux:heading size="md" class="mb-4 text-blue-600">
                        <flux:icon.building-office class="mr-2" />
                        Company Information
                    </flux:heading>
                    <div class="space-y-3">
                        <div>
                            <flux:subheading class="font-semibold">{{ $company->name }}</flux:subheading>
                        </div>
                        @if($company->vat)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">VAT Number:</span>
                            <span class="text-sm font-medium">{{ $company->vat }}</span>
                        </div>
                        @endif
                        @if($company->employees)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Employees:</span>
                            <span class="text-sm font-medium">{{ number_format($company->employees) }}</span>
                        </div>
                        @endif
                        @if($company->status)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Status:</span>
                            <flux:badge variant="{{ $company->status === 'active' ? 'outline' : 'soft' }}" size="sm">
                                {{ ucfirst($company->status) }}
                            </flux:badge>
                        </div>
                        @endif
                        @if($company->industrycode || $company->industrydesc)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Industry:</span>
                            <span class="text-sm font-medium">
                                @if($company->industrydesc)
                                    {{ $company->industrydesc }}
                                @elseif($company->industrycode)
                                    {{ $company->industrycode }}
                                @endif
                            </span>
                        </div>
                        @endif
                        @if($company->companytype)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Type:</span>
                            <div class="flex items-center gap-1">
                                <span class="text-sm font-medium">{{ $company->companytype }}</span>
                                @if($company->companydesc)
                                <flux:tooltip :content="$company->companydesc">
                                    <flux:icon.question-mark-circle class="size-4 text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                </flux:tooltip>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if($company->startdate)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Founded:</span>
                            <span class="text-sm font-medium">{{ $company->startdate->format('F j, Y') }}</span>
                        </div>
                        @endif
                        @if($company->enddate)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">End Date:</span>
                            <span class="text-sm font-medium">{{ $company->enddate->format('F j, Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            <!-- Contact Information -->
            <flux:card>
                <div class="p-4">
                    <flux:heading size="md" class="mb-4 text-green-600">
                        <flux:icon.map-pin class="mr-2" />
                        Contact Information
                    </flux:heading>
                    <div class="space-y-3">
                        @if($company->address || $company->city || $company->zipcode)
                        <div>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Address:</span>
                            <div class="text-sm font-medium mt-1">
                                @if($company->address)
                                    {{ $company->address }}<br>
                                @endif
                                @if($company->zipcode || $company->city)
                                    {{ $company->zipcode }} {{ $company->city }}
                                @endif
                            </div>
                        </div>
                        @endif
                        @if($company->phone)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Phone:</span>
                            <span class="text-sm font-medium">{{ $company->phone }}</span>
                        </div>
                        @endif
                        @if($company->email)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Email:</span>
                            <a href="mailto:{{ $company->email }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                {{ $company->email }}
                            </a>
                        </div>
                        @endif
                        @if($company->website)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Website:</span>
                            <a href="{{ $company->website }}" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                <flux:icon.arrow-top-right-on-square class="inline w-3 h-3 ml-1" />
                                Visit Website
                            </a>
                        </div>
                        @endif
                        @if($company->fax)
                        <div class="flex justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Fax:</span>
                            <span class="text-sm font-medium">{{ $company->fax }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Financial Chart Component -->
        <div class="mb-6">
            <livewire:companies.financial-chart :company="$company" :key="'financial-chart-'.$companyId" />
        </div>

        <!-- Ownership Information -->
        @if($company->owners && is_array($company->owners) && count($company->owners) > 0)
        <div class="mb-6">
            <flux:card>
                <div class="p-4">
                    <flux:heading size="md" class="mb-4 text-orange-600">
                        <flux:icon.users class="mr-2" />
                        Ownership Information
                    </flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($company->owners as $owner)
                        <div class="bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg">
                            @if(is_array($owner))
                                @foreach($owner as $key => $value)
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                    <span class="text-sm font-medium">{{ $value }}</span>
                                </div>
                                @endforeach
                            @else
                                <span class="text-sm font-medium">{{ $owner }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        </div>
        @endif
        @if($company && count($locations) >= 0)
        <!-- Job Search and Filters Component -->
        <livewire:search-filters.index
            :companies="$companies"
            :locations="$locations"
            :options="[
                'title' => 'Job Search & Filters',
                'showPerPage' => true,
                'showDateFilters' => true,
                'showCompanyFilter' => false,
                'scopedCompanyId' => $company->company_id
            ]"
            :key="'company-filters-'.$companyId"
        />

        <!-- Job Table Component -->
        <livewire:jobs.job-table
            :tableConfig="$tableConfig"
            :jobId="$jobId"
            :key="'company-jobs-'.$companyId"
        />
        @endif

    @else
    <div class="text-center">
        <p class="text-gray-500">Company not found.</p>
        @if($showBackButton)
        <flux:button
            size="sm"
            wire:click="goBackToDashboard"
            variant="outline"
            icon="arrow-left"
            class="mt-4"
        >
            Back to Companies
        </flux:button>
        @endif
    </div>
    @endif
</div>
