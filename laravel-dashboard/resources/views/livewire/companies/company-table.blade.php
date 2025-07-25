<flux:card>
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-6">
        <div class="flex justify-between items-center">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                {{ $title }} ({{ $totalResults }} results)
            </flux:heading>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $companies->count() }} results displayed
            </div>
        </div>
    </div>

    <flux:table :paginate="$companies">
        <flux:table.columns>
            @if(isset($regularColumns) && count($regularColumns) > 0)
                @foreach($regularColumns as $field => $label)
                    <flux:table.column
                        sortable
                        :sorted="$sortField === $field"
                        :direction="$sortDirection"
                        wire:click="sortBy('{{ $field }}')"
                    >
                        {{ $label }}
                    </flux:table.column>
                @endforeach
            @endif

            @if($linkToDetailsPage)
                <flux:table.column>Actions</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse($companies as $company)
                <flux:table.row :key="$company->company_id">
                    @if(isset($regularColumns) && count($regularColumns) > 0)
                        @foreach($regularColumns as $field => $label)
                            @if($field === 'name')
                                <flux:table.cell>
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $company->name }}
                                        </div>
                                        @if($company->industrydesc)
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ Str::limit($company->industrydesc, 40) }}
                                        </div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            @elseif($field === 'vat')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($company->vat)
                                    <span class="text-sm font-mono text-zinc-700 dark:text-zinc-300">
                                        {{ $company->vat }}
                                    </span>
                                    @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">No VAT</span>
                                    @endif
                                </flux:table.cell>
                            @elseif($field === 'city')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($company->city)
                                    <div class="flex items-center">
                                        <flux:icon.map-pin class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                            {{ $company->city }}
                                        </span>
                                        @if($company->zipcode)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">
                                            ({{ $company->zipcode }})
                                        </span>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">-</span>
                                    @endif
                                </flux:table.cell>
                            @elseif($field === 'employees')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($company->employees)
                                    <div class="flex items-center">
                                        <flux:icon.user-group class="mr-2 text-zinc-400 dark:text-zinc-500" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                            {{ number_format($company->employees) }}
                                        </span>
                                    </div>
                                    @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">-</span>
                                    @endif
                                </flux:table.cell>
                            @elseif($field === 'status')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($company->status)
                                    <flux:badge
                                        variant="{{ in_array(strtolower($company->status), ['aktiv', 'normal', 'active']) ? 'outline' : 'soft' }}"
                                        size="sm"
                                    >
                                        {{ $company->status }}
                                    </flux:badge>
                                    @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">-</span>
                                    @endif
                                </flux:table.cell>
                            @elseif($field === 'job_count')
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($company->job_postings_count > 0)
                                    <div class="flex flex-col space-y-1">
                                        @if($company->open_jobs_count > 0)
                                        <flux:badge variant="outline" size="sm" color="green">
                                            <flux:icon.briefcase class="mr-1" />
                                            {{ $company->open_jobs_count }} open
                                        </flux:badge>
                                        @endif
                                        @if($company->closed_jobs_count > 0)
                                        <flux:badge variant="outline" size="sm" color="gray">
                                            <flux:icon.archive-box class="mr-1" />
                                            {{ $company->closed_jobs_count }} closed
                                        </flux:badge>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">No jobs</span>
                                    @endif
                                </flux:table.cell>
                            @endif
                        @endforeach
                    @endif

                    @if($linkToDetailsPage)
                        <flux:table.cell>
                            <div class="flex space-x-2">
                                <flux:button
                                    wire:click="viewCompany({{ $company->company_id }})"
                                    size="sm"
                                    variant="outline"
                                    icon="arrow-right"
                                >
                                    View Details
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ count($regularColumns ?? []) + ($linkToDetailsPage ? 1 : 0) }}">
                        <div class="text-center text-zinc-500 dark:text-zinc-400 py-8">
                            <flux:icon.building-office class="text-4xl mb-4" />
                            <p class="text-lg">No companies found matching your criteria.</p>
                            <p class="text-sm">Try adjusting your search or filters.</p>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>
