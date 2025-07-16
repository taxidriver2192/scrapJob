<div>
    <flux:card class="mt-6">
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="md" class="text-indigo-600">
                    <flux:icon.building-office class="mr-2" />
                    {{ $title }}
                </flux:heading>
                @if($companies->total() > 0)
                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                    Showing {{ $companies->firstItem() }}-{{ $companies->lastItem() }} of {{ $companies->total() }} companies
                </span>
                @endif
            </div>

            @if($companies->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            @foreach($enabledColumns as $field => $label)
                            <th class="text-left py-3 px-4 font-medium text-zinc-700 dark:text-zinc-300">
                                @if(in_array($field, ['name', 'city', 'employees', 'status', 'job_count']))
                                <button 
                                    wire:click="sortBy('{{ $field }}')" 
                                    class="flex items-center space-x-1 hover:text-indigo-600 dark:hover:text-indigo-400"
                                >
                                    <span>{{ $label }}</span>
                                    @if($sortField === $field)
                                        @if($sortDirection === 'asc')
                                        <flux:icon.chevron-up class="w-4 h-4" />
                                        @else
                                        <flux:icon.chevron-down class="w-4 h-4" />
                                        @endif
                                    @else
                                    <flux:icon.chevron-up-down class="w-4 h-4 opacity-50" />
                                    @endif
                                </button>
                                @else
                                {{ $label }}
                                @endif
                            </th>
                            @endforeach
                            @if($linkToDetailsPage)
                            <th class="text-right py-3 px-4 font-medium text-zinc-700 dark:text-zinc-300">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($companies as $company)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            @if(array_key_exists('name', $enabledColumns))
                            <td class="py-3 px-4">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $company->name }}
                                </div>
                                @if($company->industrydesc)
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ Str::limit($company->industrydesc, 40) }}
                                </div>
                                @endif
                            </td>
                            @endif

                            @if(array_key_exists('vat', $enabledColumns))
                            <td class="py-3 px-4">
                                @if($company->vat)
                                <span class="text-sm font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ $company->vat }}
                                </span>
                                @else
                                <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">No VAT</span>
                                @endif
                            </td>
                            @endif

                            @if(array_key_exists('city', $enabledColumns))
                            <td class="py-3 px-4">
                                @if($company->city)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $company->city }}
                                </span>
                                @if($company->zipcode)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">
                                    ({{ $company->zipcode }})
                                </span>
                                @endif
                                @else
                                <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">-</span>
                                @endif
                            </td>
                            @endif

                            @if(array_key_exists('employees', $enabledColumns))
                            <td class="py-3 px-4">
                                @if($company->employees)
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ number_format($company->employees) }}
                                </span>
                                @else
                                <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">-</span>
                                @endif
                            </td>
                            @endif

                            @if(array_key_exists('status', $enabledColumns))
                            <td class="py-3 px-4">
                                @if($company->status)
                                <flux:badge 
                                    variant="{{ in_array(strtolower($company->status), ['aktiv', 'normal']) ? 'outline' : 'soft' }}" 
                                    size="sm"
                                >
                                    {{ $company->status }}
                                </flux:badge>
                                @else
                                <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">-</span>
                                @endif
                            </td>
                            @endif

                            @if(array_key_exists('job_count', $enabledColumns))
                            <td class="py-3 px-4">
                                @if($company->job_postings_count > 0)
                                <flux:badge variant="outline" size="sm">
                                    {{ $company->job_postings_count }} {{ Str::plural('job', $company->job_postings_count) }}
                                </flux:badge>
                                @else
                                <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">No jobs</span>
                                @endif
                            </td>
                            @endif

                            @if($linkToDetailsPage)
                            <td class="py-3 px-4 text-right">
                                <flux:button
                                    size="sm"
                                    variant="outline"
                                    wire:click="viewCompany({{ $company->company_id }})"
                                    icon="arrow-right"
                                >
                                    View Details
                                </flux:button>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($companies->hasPages())
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    Showing {{ $companies->firstItem() }}-{{ $companies->lastItem() }} of {{ $companies->total() }} results
                </div>
                <div class="flex items-center space-x-2">
                    @if($companies->onFirstPage())
                    <flux:button size="sm" variant="ghost" disabled icon="chevron-left">
                        Previous
                    </flux:button>
                    @else
                    <flux:button size="sm" variant="ghost" wire:click="$set('page', {{ $companies->currentPage() - 1 }})" icon="chevron-left">
                        Previous
                    </flux:button>
                    @endif

                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        Page {{ $companies->currentPage() }} of {{ $companies->lastPage() }}
                    </span>

                    @if($companies->hasMorePages())
                    <flux:button size="sm" variant="ghost" wire:click="$set('page', {{ $companies->currentPage() + 1 }})" icon="chevron-right">
                        Next
                    </flux:button>
                    @else
                    <flux:button size="sm" variant="ghost" disabled icon="chevron-right">
                        Next
                    </flux:button>
                    @endif
                </div>
            </div>
            @endif
            @else
            <div class="text-center py-12">
                <flux:icon.building-office class="mx-auto w-12 h-12 text-zinc-400 dark:text-zinc-500 mb-4" />
                <h3 class="text-lg font-medium text-zinc-700 dark:text-zinc-300 mb-2">No companies found</h3>
                <p class="text-zinc-500 dark:text-zinc-400 mb-4">
                    @if($search || $cityFilter || $statusFilter || $hasVatFilter)
                        Try adjusting your search filters to find more companies.
                    @else
                        There are no companies in the database yet.
                    @endif
                </p>
                @if($search || $cityFilter || $statusFilter || $hasVatFilter)
                <flux:button
                    size="sm"
                    variant="outline"
                    wire:click="$dispatch('filtersCleared')"
                    icon="x-circle"
                >
                    Clear All Filters
                </flux:button>
                @endif
            </div>
            @endif
        </div>
    </flux:card>
</div>
